<?php

namespace App\Livewire\Transactions;

use App\Enums\DebitCategory;
use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithImportRefresh;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Support\DateRange;
use App\Support\UnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'debit', 'pageTitle' => 'Debit'])]
#[Title('Debit')]
class DebitIndex extends Component
{
    use AuthorizesRequests;
    use WithDateRange;
    use WithImportRefresh;
    use WithPagination;
    use WithUnitScopeRefresh;

    public string $search = '';

    public string $unitTab = '';

    public string $categoryFilter = 'both';

    public string $paidThroughFilter = '';

    public string $sortField = 'txn_date';

    public string $sortDirection = 'desc';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formDate = '';

    public string $formCostCenterId = '';

    public string $formCategory = '';

    public string $formAccount = '';

    public string $formPaidThroughId = '';

    public string $formDescription = '';

    public string $formAmount = '';

    public function mount(): void
    {
        $this->formDate = now()->toDateString();
        $this->formCategory = DebitCategory::Expense->value;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUnitTab(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaidThroughFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->authorize('create', DebitTransaction::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $transaction = DebitTransaction::query()->findOrFail($id);
        $this->authorize('update', $transaction);

        $this->editingId = $transaction->id;
        $this->formDate = $transaction->txn_date->toDateString();
        $this->formCostCenterId = (string) $transaction->cost_center_id;
        $this->formCategory = $transaction->category->value;
        $this->formAccount = $transaction->account;
        $this->formPaidThroughId = (string) $transaction->paid_through_entity_id;
        $this->formDescription = $transaction->description ?? '';
        $this->formAmount = (string) $transaction->amount;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $payload = [
            'txn_date' => $validated['formDate'],
            'cost_center_id' => (int) $validated['formCostCenterId'],
            'category' => $validated['formCategory'],
            'account' => $validated['formAccount'],
            'paid_through_entity_id' => (int) $validated['formPaidThroughId'],
            'description' => $validated['formDescription'] ?: null,
            'amount' => $validated['formAmount'],
        ];

        if ($this->editingId) {
            $transaction = DebitTransaction::query()->findOrFail($this->editingId);
            $this->authorize('update', $transaction);
            $transaction->update([...$payload, 'updated_by' => auth()->id()]);
        } else {
            $this->authorize('create', DebitTransaction::class);
            DebitTransaction::query()->create([...$payload, 'created_by' => auth()->id()]);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Debit saved.');
    }

    public function delete(int $id): void
    {
        $transaction = DebitTransaction::query()->findOrFail($id);
        $this->authorize('delete', $transaction);
        $transaction->delete();
        $this->dispatch('toast', message: 'Debit deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(UnitScope $unitScope)
    {
        $range = $this->dateRange();

        return view('livewire.transactions.debit-index', [
            'transactions' => $this->transactions($range),
            'totalAmount' => $this->filteredTotal($range),
            'scopedUnits' => $this->scopedUnits(),
            'entities' => Entity::query()->active()->orderBy('name')->get(),
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'formDate' => ['required', 'date'],
            'formCostCenterId' => ['required', Rule::exists('cost_centers', 'id')],
            'formCategory' => ['required', Rule::enum(DebitCategory::class)],
            'formAccount' => ['required', 'string', 'max:120'],
            'formPaidThroughId' => ['required', Rule::exists('entities', 'id')],
            'formDescription' => ['nullable', 'string', 'max:255'],
            'formAmount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    private function filteredTotal(DateRange $range): string
    {
        return (string) $this->baseQuery($range)->sum('amount');
    }

    private function transactions(DateRange $range): LengthAwarePaginator
    {
        return $this->baseQuery($range)
            ->with(['costCenter', 'paidThrough'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(8);
    }

    private function baseQuery(DateRange $range): Builder
    {
        $unitIds = $this->scopedUnitIds();

        if ($this->unitTab !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitTab]));
        }

        $query = DebitTransaction::query()
            ->whereIn('cost_center_id', $unitIds)
            ->whereBetween('txn_date', [$range->from, $range->to]);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('description', 'like', $term)
                    ->orWhere('account', 'like', $term);
            });
        }

        if ($this->categoryFilter === 'expenses') {
            $query->where('category', DebitCategory::Expense);
        } elseif ($this->categoryFilter === 'raw') {
            $query->where('category', DebitCategory::RawMaterials);
        }

        if ($this->paidThroughFilter !== '') {
            $query->where('paid_through_entity_id', (int) $this->paidThroughFilter);
        }

        return $query;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formDate = now()->toDateString();
        $this->formCostCenterId = (string) (CostCenter::query()->orderBy('name')->value('id') ?? '');
        $this->formCategory = DebitCategory::Expense->value;
        $this->formAccount = '';
        $this->formPaidThroughId = (string) (Entity::query()->active()->orderBy('name')->value('id') ?? '');
        $this->formDescription = '';
        $this->formAmount = '';
        $this->resetValidation();
    }
}
