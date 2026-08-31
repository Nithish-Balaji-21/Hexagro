<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithImportRefresh;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\SettlementLedgerEntry;
use App\Services\Dto\EntityFundingRow;
use App\Services\Dto\PartnerSettlement;
use App\Services\FundingBreakdownService;
use App\Services\SettlementService;
use App\Support\Money;
use App\Support\UnitScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'settlement', 'pageTitle' => 'Summary & Settlement'])]
#[Title('Summary & Settlement')]
class SettlementIndex extends Component
{
    use AuthorizesRequests;
    use WithImportRefresh;
    use WithUnitScopeRefresh;

    /** cost center id or 'overall' */
    public string $selectedTab = '';

    public bool $showLedgerForm = false;

    public string $ledgerDate = '';

    public string $ledgerFromId = '';

    public string $ledgerToId = '';

    public string $ledgerAmount = '';

    public string $ledgerNote = '';

    public function mount(UnitScope $unitScope): void
    {
        $this->ledgerDate = now()->toDateString();
        $first = $unitScope->selectedUnits()->first();

        if ($first) {
            $this->selectedTab = $unitScope->isAllSelected()
                ? 'overall'
                : (string) $first->id;
        }
    }

    protected function syncUnitFilters(): void
    {
        $unitScope = app(UnitScope::class);
        $unitIds = $this->scopedUnitIds();
        $allSelected = $unitScope->isAllSelected();

        if ($allSelected) {
            if (! in_array($this->selectedTab, ['overall', ...array_map('strval', $unitIds)], true)) {
                $this->selectedTab = 'overall';
            }
        } elseif ($this->selectedTab === 'overall' || ! in_array((int) $this->selectedTab, $unitIds, true)) {
            $this->selectedTab = (string) ($unitIds[0] ?? '');
        }
    }

    public function setTab(string $tab): void
    {
        $this->selectedTab = $tab;
    }

    public function openLedgerForm(?int $fromId = null, ?int $toId = null, ?string $amount = null): void
    {
        $this->authorize('create', SettlementLedgerEntry::class);
        $this->ledgerDate = now()->toDateString();
        $this->ledgerFromId = $fromId ? (string) $fromId : '';
        $this->ledgerToId = $toId ? (string) $toId : '';
        $this->ledgerAmount = $amount ?? '';
        $this->ledgerNote = '';
        $this->showLedgerForm = true;
    }

    public function saveLedgerEntry(): void
    {
        $this->authorize('create', SettlementLedgerEntry::class);

        $validated = $this->validate([
            'ledgerDate' => ['required', 'date'],
            'ledgerFromId' => ['required', Rule::exists('entities', 'id'), 'different:ledgerToId'],
            'ledgerToId' => ['required', Rule::exists('entities', 'id')],
            'ledgerAmount' => ['required', 'numeric', 'min:0.01'],
            'ledgerNote' => ['nullable', 'string', 'max:255'],
        ]);

        SettlementLedgerEntry::query()->create([
            'txn_date' => $validated['ledgerDate'],
            'unit_scope' => $this->ledgerScope(),
            'from_entity_id' => (int) $validated['ledgerFromId'],
            'to_entity_id' => (int) $validated['ledgerToId'],
            'amount' => $validated['ledgerAmount'],
            'note' => $validated['ledgerNote'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->showLedgerForm = false;
        $this->dispatch('toast', message: 'Settlement payment logged.');
    }

    public function deleteLedgerEntry(int $id): void
    {
        $entry = SettlementLedgerEntry::query()->findOrFail($id);
        $this->authorize('delete', $entry);
        $entry->delete();
        $this->dispatch('toast', message: 'Ledger entry removed.');
    }

    public function closeLedgerForm(): void
    {
        $this->showLedgerForm = false;
    }

    public function render(
        SettlementService $settlementService,
        FundingBreakdownService $fundingBreakdownService,
        UnitScope $unitScope,
    ) {
        $unitIds = $this->scopedUnitIds();
        $allSelected = $unitScope->isAllSelected();

        $isOverall = $this->selectedTab === 'overall';

        $fundingRows = $isOverall
            ? $this->aggregateFunding($fundingBreakdownService, $unitIds)
            : $fundingBreakdownService->forCostCenter(CostCenter::query()->findOrFail((int) $this->selectedTab));

        $unitSettlement = $isOverall
            ? null
            : $settlementService->forCostCenter(CostCenter::query()->findOrFail((int) $this->selectedTab));

        $overall = $isOverall ? $settlementService->overall($unitIds) : null;

        $suggestedTransfers = $isOverall
            ? $settlementService->suggestedTransfers(
                collect($overall)->map(fn ($row): array => ['entity' => $row->entity, 'outstanding' => $row->outstanding])->all(),
            )
            : $settlementService->suggestedTransfers(
                collect($unitSettlement?->partners ?? [])->map(fn (PartnerSettlement $p): array => [
                    'entity' => $p->entity,
                    'outstanding' => $p->outstanding,
                ])->all(),
            );

        $ledgerEntries = $this->ledgerEntries($isOverall, $unitIds);
        $shareholders = Entity::query()->shareholders()->orderBy('name')->get();

        $chartData = $fundingRows
            ->filter(fn (EntityFundingRow $row): bool => Money::cmp($row->entityTotal, '0') !== 0)
            ->map(fn (EntityFundingRow $row): array => [
                'label' => $row->entity->short_name,
                'value' => (float) $row->entityTotal,
            ])->values()->all();

        return view('livewire.reports.settlement-index', [
            'scopedUnits' => CostCenter::query()->whereIn('id', $unitIds)->orderBy('name')->get(),
            'allSelected' => $allSelected,
            'isOverall' => $isOverall,
            'fundingRows' => $fundingRows,
            'unitSettlement' => $unitSettlement,
            'overall' => $overall,
            'suggestedTransfers' => $suggestedTransfers,
            'ledgerEntries' => $ledgerEntries,
            'shareholders' => $shareholders,
            'scopeLabel' => $unitScope->scopeLabel(),
            'chartData' => $chartData,
            'importRefreshVersion' => $this->importRefreshVersion,
            'chartRefreshKey' => md5(json_encode($chartData).$this->unitScopeVersion.$this->importRefreshVersion.$this->selectedTab),
        ]);
    }

    /**
     * @param  list<int>  $unitIds
     * @return Collection<int, EntityFundingRow>
     */
    private function aggregateFunding(FundingBreakdownService $service, array $unitIds): Collection
    {
        $merged = [];

        foreach ($unitIds as $unitId) {
            $costCenter = CostCenter::query()->findOrFail($unitId);

            foreach ($service->forCostCenter($costCenter) as $row) {
                $id = $row->entity->id;

                if (! isset($merged[$id])) {
                    $merged[$id] = $row;

                    continue;
                }

                $existing = $merged[$id];
                $merged[$id] = new EntityFundingRow(
                    entity: $row->entity,
                    expenses: Money::add($existing->expenses, $row->expenses),
                    rawMaterials: Money::add($existing->rawMaterials, $row->rawMaterials),
                    otherDebits: Money::add($existing->otherDebits, $row->otherDebits),
                    credits: Money::add($existing->credits, $row->credits),
                    entityTotal: Money::add($existing->entityTotal, $row->entityTotal),
                );
            }
        }

        return collect(array_values($merged));
    }

    /**
     * @param  list<int>  $unitIds
     * @return \Illuminate\Database\Eloquent\Collection<int, SettlementLedgerEntry>
     */
    private function ledgerEntries(bool $isOverall, array $unitIds)
    {
        $query = SettlementLedgerEntry::query()->with(['fromEntity', 'toEntity'])->orderByDesc('txn_date');

        if ($isOverall) {
            $names = CostCenter::query()->whereIn('id', $unitIds)->pluck('name')->all();
            $query->where(function ($inner) use ($names): void {
                $inner->where('unit_scope', config('hexagro.overall_scope', 'Overall'))
                    ->orWhereIn('unit_scope', $names);
            });
        } else {
            $unit = CostCenter::query()->findOrFail((int) $this->selectedTab);
            $query->where('unit_scope', $unit->name);
        }

        return $query->get();
    }

    private function ledgerScope(): string
    {
        if ($this->selectedTab === 'overall') {
            return (string) config('hexagro.overall_scope', 'Overall');
        }

        return CostCenter::query()->findOrFail((int) $this->selectedTab)->name;
    }
}
