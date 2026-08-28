<?php

namespace App\Livewire\Finance;

use App\Models\BankingSnapshot;
use App\Services\BankingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'banking', 'pageTitle' => 'Banking'])]
#[Title('Banking')]
class BankingIndex extends Component
{
    use AuthorizesRequests;

    public bool $showForm = false;

    public string $formAsOf = '';

    public string $formCurrent = '';

    public string $formCcLimit = '';

    public string $formCcUtilised = '';

    public string $formTermLoan = '';

    public string $formAlamUtilised = '';

    public function mount(BankingService $bankingService): void
    {
        $position = $bankingService->current();

        if ($position) {
            $s = $position->snapshot;
            $this->formAsOf = $s->as_of_date->toDateString();
            $this->formCurrent = (string) $s->current_balance;
            $this->formCcLimit = (string) $s->cc_limit;
            $this->formCcUtilised = (string) $s->cc_utilised;
            $this->formTermLoan = (string) $s->term_loan;
            $this->formAlamUtilised = (string) $s->alam_utilised;
        } else {
            $this->formAsOf = now()->toDateString();
        }
    }

    public function openEditForm(): void
    {
        $this->authorize('create', BankingSnapshot::class);
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', BankingSnapshot::class);

        $validated = $this->validate([
            'formAsOf' => ['required', 'date'],
            'formCurrent' => ['required', 'numeric'],
            'formCcLimit' => ['required', 'numeric', 'min:0'],
            'formCcUtilised' => ['required', 'numeric', 'min:0'],
            'formTermLoan' => ['required', 'numeric', 'min:0'],
            'formAlamUtilised' => ['required', 'numeric'],
        ]);

        BankingSnapshot::query()->create([
            'as_of_date' => $validated['formAsOf'],
            'current_balance' => $validated['formCurrent'],
            'cc_limit' => $validated['formCcLimit'],
            'cc_utilised' => $validated['formCcUtilised'],
            'term_loan' => $validated['formTermLoan'],
            'alam_utilised' => $validated['formAlamUtilised'],
            'created_by' => auth()->id(),
        ]);

        $this->showForm = false;
        $this->dispatch('toast', message: 'Banking snapshot saved.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function render(BankingService $bankingService)
    {
        return view('livewire.finance.banking-index', [
            'position' => $bankingService->current(),
        ]);
    }
}
