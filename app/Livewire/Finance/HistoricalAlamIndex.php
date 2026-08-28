<?php

namespace App\Livewire\Finance;

use App\Models\HistoricalAlamExpense;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'historicalAlam', 'pageTitle' => 'Historical Alam Expenses'])]
#[Title('Historical Alam Expenses')]
class HistoricalAlamIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formDate = '';

    public string $formAccount = '';

    public string $formDescription = '';

    public string $formAmount = '';

    public function mount(): void
    {
        $this->formDate = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->authorize('create', HistoricalAlamExpense::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $entry = HistoricalAlamExpense::query()->findOrFail($id);
        $this->authorize('update', $entry);

        $this->editingId = $entry->id;
        $this->formDate = $entry->txn_date->toDateString();
        $this->formAccount = $entry->account;
        $this->formDescription = $entry->description ?? '';
        $this->formAmount = (string) $entry->amount;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formDate' => ['required', 'date'],
            'formAccount' => ['required', 'string', 'max:150'],
            'formDescription' => ['nullable', 'string', 'max:255'],
            'formAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $payload = [
            'txn_date' => $validated['formDate'],
            'account' => $validated['formAccount'],
            'description' => $validated['formDescription'] ?: null,
            'amount' => $validated['formAmount'],
        ];

        if ($this->editingId) {
            $entry = HistoricalAlamExpense::query()->findOrFail($this->editingId);
            $this->authorize('update', $entry);
            $entry->update($payload);
        } else {
            $this->authorize('create', HistoricalAlamExpense::class);
            HistoricalAlamExpense::query()->create([...$payload, 'created_by' => auth()->id()]);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Historical entry saved.');
    }

    public function delete(int $id): void
    {
        $entry = HistoricalAlamExpense::query()->findOrFail($id);
        $this->authorize('delete', $entry);
        $entry->delete();
        $this->dispatch('toast', message: 'Entry deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        $total = (string) HistoricalAlamExpense::query()->sum('amount');
        $sharePct = (string) config('hexagro.hist_alam_share_pct', 0.66666);
        $shareAmount = Money::mul($total, $sharePct);

        return view('livewire.finance.historical-alam-index', [
            'entries' => $this->entries(),
            'total' => $total,
            'shareAmount' => $shareAmount,
            'sharePct' => $sharePct,
        ]);
    }

    private function entries(): LengthAwarePaginator
    {
        return $this->baseQuery()->orderByDesc('txn_date')->paginate(10);
    }

    private function baseQuery(): Builder
    {
        $query = HistoricalAlamExpense::query();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('description', 'like', $term)
                    ->orWhere('account', 'like', $term);
            });
        }

        return $query;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formDate = now()->toDateString();
        $this->formAccount = '';
        $this->formDescription = '';
        $this->formAmount = '';
        $this->resetValidation();
    }
}
