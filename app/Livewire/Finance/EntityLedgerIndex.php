<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\Entity;
use App\Services\EntityLedgerService;
use App\Support\Money;
use App\Support\UnitScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'entityLedgers', 'pageTitle' => 'Ledger Book'])]
#[Title('Ledger Book')]
class EntityLedgerIndex extends Component
{
    use WithDateRange;
    use WithUnitScopeRefresh;

    public string $selectedEntityId = '';

    public function mount(): void
    {
        $this->selectedEntityId = (string) (Entity::ledgerBookEntities()->first()?->id ?? '');
    }

    public function setEntity(int $entityId): void
    {
        $this->selectedEntityId = (string) $entityId;
    }

    public function exportPdf(EntityLedgerService $ledgerService, UnitScope $unitScope)
    {
        $entityId = (int) $this->selectedEntityId;

        if ($entityId <= 0) {
            return null;
        }

        $entity = Entity::query()->findOrFail($entityId);
        $range = $this->dateRange();
        $unitIds = $this->scopedUnitIds();
        $rows = $ledgerService->rows($entityId, $unitIds, $range);
        $openingBalance = $ledgerService->openingBalance($entityId, $unitIds, $range);

        $totalDebit = $rows->reduce(fn (string $c, $row): string => Money::add($c, $row->debit), Money::zero());
        $totalCredit = $rows->reduce(fn (string $c, $row): string => Money::add($c, $row->credit), Money::zero());
        $closing = $ledgerService->closingBalance($entityId, $unitIds, $range);

        $filename = 'ledger-'.str($entity->short_name)->slug().'-'.($range->from ?? 'all').'.pdf';

        return response()->streamDownload(
            function () use ($entity, $range, $rows, $openingBalance, $totalDebit, $totalCredit, $closing, $unitScope): void {
                echo Pdf::loadView('pdf.entity-ledger', [
                    'entity' => $entity,
                    'range' => $range,
                    'rows' => $rows,
                    'openingBalance' => $openingBalance,
                    'totalDebit' => $totalDebit,
                    'totalCredit' => $totalCredit,
                    'closing' => $closing,
                    'scopeLabel' => $unitScope->scopeLabel(),
                ])->output();
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function render(EntityLedgerService $ledgerService, UnitScope $unitScope)
    {
        $entityId = (int) $this->selectedEntityId;
        $range = $this->dateRange();
        $unitIds = $this->scopedUnitIds();
        $rows = $entityId > 0
            ? $ledgerService->rows($entityId, $unitIds, $range)
            : collect();
        $openingBalance = $entityId > 0
            ? $ledgerService->openingBalance($entityId, $unitIds, $range)
            : Money::zero();

        $totalDebit = $rows->reduce(fn (string $c, $row): string => Money::add($c, $row->debit), Money::zero());
        $totalCredit = $rows->reduce(fn (string $c, $row): string => Money::add($c, $row->credit), Money::zero());
        $closing = $entityId > 0
            ? $ledgerService->closingBalance($entityId, $unitIds, $range)
            : Money::zero();

        $selectedEntity = $entityId > 0 ? Entity::query()->find($entityId) : null;

        return view('livewire.finance.entity-ledger-index', [
            'entityGroups' => Entity::ledgerBookGroups(),
            'entities' => Entity::ledgerBookEntities()->keyBy('name'),
            'selectedEntity' => $selectedEntity,
            'rows' => $rows,
            'openingBalance' => $openingBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'closing' => $closing,
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
        ]);
    }
}
