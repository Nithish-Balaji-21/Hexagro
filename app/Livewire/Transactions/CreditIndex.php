<?php

namespace App\Livewire\Transactions;

use App\Enums\CreditType;
use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Models\CreditTransaction;
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

#[Layout('components.layouts.shell', ['currentPage' => 'credit', 'pageTitle' => 'Credit'])]
#[Title('Credit')]
class CreditIndex extends Component
{
    use AuthorizesRequests;
    use WithDateRange;
    use WithPagination;
    use WithUnitScopeRefresh;

    public string $search = '';

    public string $unitTab = '';

    public string $typeFilter = '';

    public string $receivedToFilter = '';

    public string $sortField = 'txn_date';

    public string $sortDirection = 'desc';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formDate = '';

    public string $formCostCenterId = '';

    public string $formCreditType = '';

    public string $formReceivedToId = '';

    public string $formDescription = '';

    public string $formAmount = '';

    public function mount(): void
    {
        $this->formDate = now()->toDateString();
        $this->formCreditType = CreditType::Sales->value;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUnitTab(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedReceivedToFilter(): void
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
        $this->authorize('create', CreditTransaction::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $transaction = CreditTransaction::query()->findOrFail($id);
        $this->authorize('update', $transaction);

        $this->editingId = $transaction->id;
        $this->formDate = $transaction->txn_date->toDateString();
        $this->formCostCenterId = (string) $transaction->cost_center_id;
        $this->formCreditType = $transaction->credit_type->value;
        $this->formReceivedToId = (string) $transaction->received_to_entity_id;
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
            'credit_type' => $validated['formCreditType'],
            'received_to_entity_id' => (int) $validated['formReceivedToId'],
            'description' => $validated['formDescription'] ?: null,
            'amount' => $validated['formAmount'],
        ];

        if ($this->editingId) {
            $transaction = CreditTransaction::query()->findOrFail($this->editingId);
            $this->authorize('update', $transaction);
            $transaction->update([...$payload, 'updated_by' => auth()->id()]);
        } else {
            $this->authorize('create', CreditTransaction::class);
            CreditTransaction::query()->create([...$payload, 'created_by' => auth()->id()]);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Credit saved.');
    }

    public function delete(int $id): void
    {
        $transaction = CreditTransaction::query()->findOrFail($id);
        $this->authorize('delete', $transaction);
        $transaction->delete();
        $this->dispatch('toast', message: 'Credit deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(UnitScope $unitScope)
    {
        $visibleUnits = $unitScope->visibleUnits();
        $range = $this->dateRange();

        return view('livewire.transactions.credit-index', [
            'transactions' => $this->transactions($range),
            'totalAmount' => $this->filteredTotal($range),
            'visibleUnits' => $visibleUnits,
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
            'formCreditType' => ['required', Rule::enum(CreditType::class)],
            'formReceivedToId' => ['required', Rule::exists('entities', 'id')],
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
            ->with(['costCenter', 'receivedTo'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(8);
    }

    private function baseQuery(DateRange $range): Builder
    {
        $unitIds = $this->scopedUnitIds();

        if ($this->unitTab !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitTab]));
        }

        $query = CreditTransaction::query()
            ->whereIn('cost_center_id', $unitIds)
            ->whereBetween('txn_date', [$range->from, $range->to]);

        if ($this->search !== '') {
            $query->where('description', 'like', '%'.$this->search.'%');
        }

        if ($this->typeFilter === 'sales') {
            $query->where('credit_type', CreditType::Sales);
        } elseif ($this->typeFilter === 'returns') {
            $query->whereIn('credit_type', [
                CreditType::VendorReturn,
                CreditType::EmployeeReturn,
            ]);
        }

        if ($this->receivedToFilter !== '') {
            $query->where('received_to_entity_id', (int) $this->receivedToFilter);
        }

        return $query;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formDate = now()->toDateString();
        $this->formCostCenterId = (string) (CostCenter::query()->orderBy('name')->value('id') ?? '');
        $this->formCreditType = CreditType::Sales->value;
        $this->formReceivedToId = (string) (Entity::query()->active()->orderBy('name')->value('id') ?? '');
        $this->formDescription = '';
        $this->formAmount = '';
        $this->resetValidation();
    }
}
