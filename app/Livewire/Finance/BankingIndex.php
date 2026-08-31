<?php

namespace App\Livewire\Finance;

use App\Models\BankingSnapshot;
use App\Services\BankingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
        $this->formAsOf = now()->toDateString();
        $this->formCurrent = '';
        $this->formCcUtilised = '';
        $this->formTermLoan = '';

        // Retain latest limits/limits defaults if available
        $latest = BankingSnapshot::query()->orderByDesc('as_of_date')->first();
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

        $validated = $this->validate([
            'formAsOf' => ['required', 'date'],
            'formCurrent' => ['required', 'numeric'],
            'formCcLimit' => ['required', 'numeric', 'min:0'],
            'formCcUtilised' => ['required', 'numeric', 'min:0'],
            'formTermLoan' => ['required', 'numeric', 'min:0'],
            'formTlLimit' => ['required', 'numeric', 'min:0'],
        ]);

        $payload = [
            'as_of_date' => $validated['formAsOf'],
            'current_balance' => $validated['formCurrent'],
            'cc_limit' => $validated['formCcLimit'],
            'cc_utilised' => $validated['formCcUtilised'],
            'term_loan' => $validated['formTermLoan'],
            'tl_limit' => $validated['formTlLimit'],
            'alam_utilised' => $bankingService->alamUtilisedAsOf($validated['formAsOf']),
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
        return view('livewire.finance.banking-index', [
            'position' => $bankingService->current(),
            'snapshots' => BankingSnapshot::query()->with('createdBy')->orderByDesc('as_of_date')->paginate(10),
        ]);
    }
}
