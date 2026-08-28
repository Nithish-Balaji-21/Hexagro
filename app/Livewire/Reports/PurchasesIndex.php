<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\Purchase;
use App\Support\Money;
use App\Support\UnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'purchases', 'pageTitle' => 'Purchases'])]
#[Title('Purchases')]
class PurchasesIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithUnitScopeRefresh;

    public string $search = '';

    public string $unitFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formCostCenterId = '';

    public string $formVendor = '';

    public string $formBilled = '';

    public string $formPaid = '';

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
        $this->authorize('create', Purchase::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $purchase = Purchase::query()->findOrFail($id);
        $this->authorize('update', $purchase);

        $this->editingId = $purchase->id;
        $this->formCostCenterId = (string) $purchase->cost_center_id;
        $this->formVendor = $purchase->vendor_name;
        $this->formBilled = $purchase->total_billed !== null ? (string) $purchase->total_billed : '';
        $this->formPaid = (string) $purchase->total_paid;
        $this->formNotes = $purchase->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formCostCenterId' => ['required', Rule::exists('cost_centers', 'id')],
            'formVendor' => ['required', 'string', 'max:150'],
            'formBilled' => ['nullable', 'numeric', 'min:0'],
            'formPaid' => ['required', 'numeric', 'min:0'],
            'formNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = [
            'cost_center_id' => (int) $validated['formCostCenterId'],
            'vendor_name' => $validated['formVendor'],
            'total_billed' => $validated['formBilled'] !== '' ? $validated['formBilled'] : null,
            'total_paid' => $validated['formPaid'],
            'notes' => $validated['formNotes'] ?: null,
        ];

        if ($this->editingId) {
            $purchase = Purchase::query()->findOrFail($this->editingId);
            $this->authorize('update', $purchase);
            $purchase->update($payload);
        } else {
            $this->authorize('create', Purchase::class);
            Purchase::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Purchase saved.');
    }

    public function delete(int $id): void
    {
        $purchase = Purchase::query()->findOrFail($id);
        $this->authorize('delete', $purchase);
        $purchase->delete();
        $this->dispatch('toast', message: 'Purchase deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(UnitScope $unitScope)
    {
        $rows = $this->purchases();

        return view('livewire.reports.purchases-index', [
            'purchases' => $rows,
            'totalBilled' => (string) $this->baseQuery()->whereNotNull('total_billed')->sum('total_billed'),
            'totalPaid' => (string) $this->baseQuery()->sum('total_paid'),
            'totalOutstanding' => (string) $this->baseQuery()->whereNotNull('total_billed')->sum('balance'),
            'visibleUnits' => $unitScope->visibleUnits(),
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
        ]);
    }

    private function purchases(): LengthAwarePaginator
    {
        return $this->baseQuery()->with('costCenter')->orderBy('vendor_name')->paginate(10);
    }

    private function baseQuery(): Builder
    {
        $unitIds = $this->scopedUnitIds();

        if ($this->unitFilter !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitFilter]));
        }

        $query = Purchase::query()->whereIn('cost_center_id', $unitIds);

        if ($this->search !== '') {
            $query->where('vendor_name', 'like', '%'.$this->search.'%');
        }

        return $query;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formCostCenterId = (string) (CostCenter::query()->orderBy('name')->value('id') ?? '');
        $this->formVendor = '';
        $this->formBilled = '';
        $this->formPaid = '0';
        $this->formNotes = '';
        $this->resetValidation();
    }
}
