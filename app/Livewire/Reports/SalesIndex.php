<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithImportRefresh;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Models\Sale;
use App\Support\UnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'sales', 'pageTitle' => 'Sales'])]
#[Title('Sales')]
class SalesIndex extends Component
{
    use AuthorizesRequests;
    use WithImportRefresh;
    use WithPagination;
    use WithUnitScopeRefresh;

    public string $search = '';

    public string $unitFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formCostCenterId = '';

    public string $formCustomer = '';

    public string $formInvoiced = '';

    public string $formReceived = '';

    public string $formNotes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUnitFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->authorize('create', Sale::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $sale = Sale::query()->findOrFail($id);
        $this->authorize('update', $sale);

        $this->editingId = $sale->id;
        $this->formCostCenterId = (string) $sale->cost_center_id;
        $this->formCustomer = $sale->customer_name;
        $this->formInvoiced = (string) $sale->total_invoiced;
        $this->formReceived = (string) $sale->total_received;
        $this->formNotes = $sale->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formCostCenterId' => ['required', Rule::exists('cost_centers', 'id')],
            'formCustomer' => ['required', 'string', 'max:150'],
            'formInvoiced' => ['required', 'numeric', 'min:0'],
            'formReceived' => ['required', 'numeric', 'min:0'],
            'formNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = [
            'cost_center_id' => (int) $validated['formCostCenterId'],
            'customer_name' => $validated['formCustomer'],
            'total_invoiced' => $validated['formInvoiced'],
            'total_received' => $validated['formReceived'],
            'notes' => $validated['formNotes'] ?: null,
        ];

        if ($this->editingId) {
            $sale = Sale::query()->findOrFail($this->editingId);
            $this->authorize('update', $sale);
            $sale->update($payload);
        } else {
            $this->authorize('create', Sale::class);
            Sale::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Sale saved.');
    }

    public function delete(int $id): void
    {
        $sale = Sale::query()->findOrFail($id);
        $this->authorize('delete', $sale);
        $sale->delete();
        $this->dispatch('toast', message: 'Sale deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(UnitScope $unitScope)
    {
        return view('livewire.reports.sales-index', [
            'sales' => $this->sales(),
            'totalInvoiced' => (string) $this->baseQuery()->sum('total_invoiced'),
            'totalReceived' => (string) $this->baseQuery()->sum('total_received'),
            'totalOutstanding' => (string) $this->baseQuery()->sum('balance'),
            'scopedUnits' => $this->scopedUnits(),
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
        ]);
    }

    private function sales(): LengthAwarePaginator
    {
        return $this->baseQuery()->with('costCenter')->orderBy('customer_name')->paginate(10);
    }

    private function baseQuery(): Builder
    {
        $unitIds = $this->scopedUnitIds();

        if ($this->unitFilter !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitFilter]));
        }

        $query = Sale::query()->whereIn('cost_center_id', $unitIds);

        if ($this->search !== '') {
            $query->where('customer_name', 'like', '%'.$this->search.'%');
        }

        return $query;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formCostCenterId = (string) (CostCenter::query()->orderBy('name')->value('id') ?? '');
        $this->formCustomer = '';
        $this->formInvoiced = '';
        $this->formReceived = '0';
        $this->formNotes = '';
        $this->resetValidation();
    }
}
