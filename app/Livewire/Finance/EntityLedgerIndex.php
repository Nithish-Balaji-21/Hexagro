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
        $user = auth()->user();
        $allowedNames = $this->getAllowedLedgerEntities($user);
        $firstAllowedEntity = Entity::ledgerBookEntities()
            ->filter(fn (Entity $entity) => in_array($entity->name, $allowedNames, true))
            ->first();

        $this->selectedEntityId = (string) ($firstAllowedEntity?->id ?? '');
    }

    public function setEntity(int $entityId): void
    {
        $user = auth()->user();
        $allowedNames = $this->getAllowedLedgerEntities($user);
        $entity = Entity::query()->findOrFail($entityId);

        if (! in_array($entity->name, $allowedNames, true)) {
            abort(403, 'Unauthorized.');
        }

        $this->selectedEntityId = (string) $entityId;
    }

    public function exportPdf(EntityLedgerService $ledgerService, UnitScope $unitScope)
    {
        $entityId = (int) $this->selectedEntityId;

        if ($entityId <= 0) {
            return null;
        }

        $entity = Entity::query()->findOrFail($entityId);
        $user = auth()->user();
        $allowedNames = $this->getAllowedLedgerEntities($user);

        if (! in_array($entity->name, $allowedNames, true)) {
            abort(403, 'Unauthorized.');
        }

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
        $user = auth()->user();
        $allowedNames = $this->getAllowedLedgerEntities($user);

        $entities = Entity::ledgerBookEntities()
            ->filter(fn (Entity $entity) => in_array($entity->name, $allowedNames, true))
            ->keyBy('name');

        $entityId = (int) $this->selectedEntityId;
        $selectedEntity = $entityId > 0 ? Entity::query()->find($entityId) : null;

        if ($selectedEntity && ! in_array($selectedEntity->name, $allowedNames, true)) {
            $selectedEntity = null;
            $entityId = 0;
        }

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

        return view('livewire.finance.entity-ledger-index', [
            'entityGroups' => Entity::ledgerBookGroups(),
            'entities' => $entities,
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

    private function getAllowedLedgerEntities(\App\Models\User $user): array
    {
        if ($user->isAdmin()) {
            return [
                'Shareholder - Jagadeesan',
                'Shareholder - Jagadeshwaran',
                'Shareholder - Vellingiri',
                'Vikas',
                'Payable to Alam',
                'Union Bank - CC',
                'Union Bank - Current',
                'Union Bank - Term Loan',
            ];
        }

        if ($user->configKey() === 'vikas') {
            return ['Vikas'];
        }

        $ownEntityName = match ($user->name) {
            'Jagadeesan' => 'Shareholder - Jagadeesan',
            'Jagadeshwaran' => 'Shareholder - Jagadeshwaran',
            'Vellingiri' => 'Shareholder - Vellingiri',
            'Vikas' => 'Vikas',
            default => null,
        };

        $allowed = [];
        if ($ownEntityName) {
            $allowed[] = $ownEntityName;
        }

        // Add bank accounts
        $allowed[] = 'Union Bank - CC';
        $allowed[] = 'Union Bank - Current';
        $allowed[] = 'Union Bank - Term Loan';

        return $allowed;
    }
}
