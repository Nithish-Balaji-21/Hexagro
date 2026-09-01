<?php

namespace App\Livewire\Finance;

use App\Models\BankingSnapshot;
use App\Services\BankingService;
use App\Support\Inr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'banking', 'pageTitle' => 'Banking'])]
#[Title('Banking')]
class BankingIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formAsOf = '';

    public string $formCurrent = '';

    public string $formCcLimit = '';

    public string $formCcUtilised = '';

    public string $formTermLoan = '';

    public string $formTlLimit = '';

    public function mount(): void
    {
        if (auth()->user()->configKey() === 'vikas') {
            abort(403, 'Unauthorized.');
        }

        $this->formAsOf = now()->toDateString();
        $this->formCcLimit = '0';
        $this->formTlLimit = '13500000';
    }

    public function openCreateForm(): void
    {
        $this->authorize('create', BankingSnapshot::class);
        $this->editingId = null;

        $latestDate = BankingSnapshot::query()
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->value('as_of_date');

        $defaultDate = $latestDate
            ? \Illuminate\Support\Carbon::parse($latestDate)->addDay()->toDateString()
            : now()->toDateString();

        if ($defaultDate > now()->toDateString()) {
            $defaultDate = now()->toDateString();
        }

        $this->formAsOf = $defaultDate;
        $this->formCurrent = '';
        $this->formCcUtilised = '';
        $this->formTermLoan = '';

        $latest = BankingSnapshot::query()->orderByDesc('as_of_date')->orderByDesc('id')->first();
        if ($latest) {
            $this->formCcLimit = (string) $latest->cc_limit;
            $this->formTlLimit = (string) $latest->tl_limit;
        } else {
            $this->formCcLimit = '0';
            $this->formTlLimit = '13500000';
        }

        $this->showForm = true;
    }

    public function openEditForm(?int $id = null): void
    {
        $this->authorize('create', BankingSnapshot::class);

        if ($id === null) {
            $this->openCreateForm();

            return;
        }

        if ($id !== $this->latestSnapshotId()) {
            $this->dispatch('toast', message: 'Only the latest banking snapshot can be edited.');

            return;
        }

        $snapshot = BankingSnapshot::query()->findOrFail($id);
        $this->editingId = $snapshot->id;
        $this->formAsOf = $snapshot->as_of_date->toDateString();
        $this->formCurrent = (string) $snapshot->current_balance;
        $this->formCcLimit = (string) $snapshot->cc_limit;
        $this->formCcUtilised = (string) $snapshot->cc_utilised;
        $this->formTermLoan = (string) $snapshot->term_loan;
        $this->formTlLimit = (string) $snapshot->tl_limit;
        $this->showForm = true;
    }

    public function save(BankingService $bankingService): void
    {
        $this->authorize('create', BankingSnapshot::class);

        if ($this->editingId !== null && $this->editingId !== $this->latestSnapshotId()) {
            $this->dispatch('toast', message: 'Only the latest banking snapshot can be edited.');

            return;
        }

        $parsedDate = Inr::parseDatePicker($this->formAsOf);

        $validated = $this->validate([
            'formAsOf' => ['required', 'string'],
            'formCurrent' => ['required', 'numeric'],
            'formCcLimit' => ['required', 'numeric', 'min:0'],
            'formCcUtilised' => ['required', 'numeric', 'min:0'],
            'formTermLoan' => ['required', 'numeric', 'min:0'],
            'formTlLimit' => ['required', 'numeric', 'min:0'],
        ]);

        if ($parsedDate === null) {
            $this->addError('formAsOf', 'Enter a valid date.');

            return;
        }

        $latestDate = BankingSnapshot::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->value('as_of_date');

        if ($latestDate) {
            $minAllowedDate = \Illuminate\Support\Carbon::parse($latestDate)->addDay()->toDateString();
            if ($parsedDate < $minAllowedDate) {
                $this->addError('formAsOf', 'Date must be on or after '.Inr::formatDatePicker($minAllowedDate).'.');

                return;
            }
        }

        if ($parsedDate > now()->toDateString()) {
            $this->addError('formAsOf', 'Future dates cannot be selected.');

            return;
        }

        $uniqueRule = Rule::unique('banking_snapshots', 'as_of_date');
        if ($this->editingId) {
            $uniqueRule = $uniqueRule->ignore($this->editingId);
        }

        $validator = validator(['as_of_date' => $parsedDate], [
            'as_of_date' => ['required', 'date', $uniqueRule],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->get('as_of_date') as $message) {
                $this->addError('formAsOf', $message);
            }

            return;
        }

        $payload = [
            'as_of_date' => $parsedDate,
            'current_balance' => $validated['formCurrent'],
            'cc_limit' => $validated['formCcLimit'],
            'cc_utilised' => $validated['formCcUtilised'],
            'term_loan' => $validated['formTermLoan'],
            'tl_limit' => $validated['formTlLimit'],
            'alam_utilised' => $bankingService->alamUtilisedAsOf($parsedDate),
        ];

        if ($this->editingId) {
            BankingSnapshot::query()->findOrFail($this->editingId)->update($payload);
            $this->dispatch('toast', message: 'Banking snapshot updated.');
        } else {
            BankingSnapshot::query()->create(array_merge($payload, [
                'created_by' => auth()->id(),
            ]));
            $this->dispatch('toast', message: 'Banking snapshot saved.');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $this->authorize('create', BankingSnapshot::class);

        if ($id !== $this->latestSnapshotId()) {
            $this->dispatch('toast', message: 'Only the latest banking snapshot can be deleted.');

            return;
        }

        BankingSnapshot::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Banking snapshot deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function render(BankingService $bankingService)
    {
        $latestDate = BankingSnapshot::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->value('as_of_date');

        $minAsOfDate = $latestDate
            ? \Illuminate\Support\Carbon::parse($latestDate)->addDay()->toDateString()
            : null;

        $maxAsOfDate = now()->toDateString();

        return view('livewire.finance.banking-index', [
            'position' => $bankingService->current(),
            'snapshots' => BankingSnapshot::query()->with('createdBy')->orderByDesc('as_of_date')->orderByDesc('id')->paginate(10),
            'latestSnapshotId' => $this->latestSnapshotId(),
            'minAsOfDate' => $minAsOfDate,
            'maxAsOfDate' => $maxAsOfDate,
            'minAsOfDateLabel' => $minAsOfDate !== null
                ? Inr::formatDatePicker($minAsOfDate)
                : null,
        ]);
    }

    private function latestSnapshotId(): ?int
    {
        return BankingSnapshot::query()
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->value('id');
    }
}
