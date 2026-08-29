<?php

namespace App\Livewire\Transactions;

use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\Transfer;
use App\Support\Money;
use App\Support\UnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'transfers', 'pageTitle' => 'Transfers'])]
#[Title('Transfers')]
class TransferIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithUnitScopeRefresh;

    public string $unitTab = '';

    public string $fromFilter = '';

    public string $toFilter = '';

    public string $sortField = 'txn_date';

    public string $sortDirection = 'desc';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formDate = '';

    public string $formCostCenterId = '';

    public string $formFromId = '';

    public string $formToId = '';

    public string $formNote = '';

    public string $formAmount = '';

    public function mount(): void
    {
        $this->formDate = now()->toDateString();
    }

    public function updatedUnitTab(): void
    {
        $this->resetPage();
    }

    public function updatedFromFilter(): void
    {
        $this->resetPage();
    }

    public function updatedToFilter(): void
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
        $this->authorize('create', Transfer::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $transfer = Transfer::query()->findOrFail($id);
        $this->authorize('update', $transfer);

        $this->editingId = $transfer->id;
        $this->formDate = $transfer->txn_date->toDateString();
        $this->formCostCenterId = (string) $transfer->cost_center_id;
        $this->formFromId = (string) $transfer->from_entity_id;
        $this->formToId = (string) $transfer->to_entity_id;
        $this->formNote = $transfer->note ?? '';
        $this->formAmount = (string) $transfer->amount;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formDate' => ['required', 'date'],
            'formCostCenterId' => ['required', Rule::exists('cost_centers', 'id')],
            'formFromId' => ['required', Rule::exists('entities', 'id'), 'different:formToId'],
            'formToId' => ['required', Rule::exists('entities', 'id')],
            'formNote' => ['nullable', 'string', 'max:255'],
            'formAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $payload = [
            'txn_date' => $validated['formDate'],
            'cost_center_id' => (int) $validated['formCostCenterId'],
            'from_entity_id' => (int) $validated['formFromId'],
            'to_entity_id' => (int) $validated['formToId'],
            'note' => $validated['formNote'] ?: null,
            'amount' => $validated['formAmount'],
        ];

        if ($this->editingId) {
            $transfer = Transfer::query()->findOrFail($this->editingId);
            $this->authorize('update', $transfer);
            $transfer->update($payload);
        } else {
            $this->authorize('create', Transfer::class);
            Transfer::query()->create([...$payload, 'created_by' => auth()->id()]);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Transfer saved.');
    }

    public function delete(int $id): void
    {
        $transfer = Transfer::query()->findOrFail($id);
        $this->authorize('delete', $transfer);
        $transfer->delete();
        $this->dispatch('toast', message: 'Transfer deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(UnitScope $unitScope)
    {
        $unitIds = $this->scopedUnitIds();
        if ($this->unitTab !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitTab]));
        }

        return view('livewire.transactions.transfer-index', [
            'transfers' => $this->transfers(),
            'totalAmount' => (string) $this->baseQuery()->sum('amount'),
            'entityNets' => $this->entityNetBalances($unitIds),
            'scopedUnits' => $this->scopedUnits(),
            'entities' => Entity::query()->active()->orderBy('name')->get(),
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
        ]);
    }

    private function transfers(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->with(['costCenter', 'fromEntity', 'toEntity'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(8);
    }

    private function baseQuery(): Builder
    {
        $unitIds = $this->scopedUnitIds();
        if ($this->unitTab !== '') {
            $unitIds = array_values(array_intersect($unitIds, [(int) $this->unitTab]));
        }

        $query = Transfer::query()->whereIn('cost_center_id', $unitIds);

        if ($this->fromFilter !== '') {
            $query->where('from_entity_id', (int) $this->fromFilter);
        }

        if ($this->toFilter !== '') {
            $query->where('to_entity_id', (int) $this->toFilter);
        }

        return $query;
    }

    /**
     * @param  list<int>  $unitIds
     * @return Collection<int, array{entity: Entity, net: string}>
     */
    private function entityNetBalances(array $unitIds): Collection
    {
        $nets = Entity::query()->active()->orderBy('name')->get()->mapWithKeys(
            fn (Entity $entity): array => [$entity->id => ['entity' => $entity, 'net' => Money::zero()]],
        );

        Transfer::query()
            ->whereIn('cost_center_id', $unitIds)
            ->get()
            ->each(function (Transfer $transfer) use ($nets): void {
                $from = $nets[$transfer->from_entity_id] ?? null;
                $to = $nets[$transfer->to_entity_id] ?? null;

                if ($from) {
                    $from['net'] = Money::sub($from['net'], (string) $transfer->amount);
                    $nets[$transfer->from_entity_id] = $from;
                }

                if ($to) {
                    $to['net'] = Money::add($to['net'], (string) $transfer->amount);
                    $nets[$transfer->to_entity_id] = $to;
                }
            });

        return $nets->values();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formDate = now()->toDateString();
        $this->formCostCenterId = (string) (CostCenter::query()->orderBy('name')->value('id') ?? '');
        $entities = Entity::query()->active()->orderBy('name')->get();
        $this->formFromId = (string) ($entities->first()?->id ?? '');
        $this->formToId = (string) ($entities->skip(1)->first()?->id ?? '');
        $this->formNote = '';
        $this->formAmount = '';
        $this->resetValidation();
    }
}
