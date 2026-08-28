<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Models\Entity;
use App\Services\EntityLedgerService;
use App\Support\Money;
use App\Support\UnitScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'entityLedgers', 'pageTitle' => 'Ledger Book'])]
#[Title('Ledger Book')]
class EntityLedgerIndex extends Component
{
    use WithUnitScopeRefresh;

    public string $selectedEntityId = '';

    public function mount(): void
    {
        $this->selectedEntityId = (string) (Entity::query()->active()->orderBy('name')->value('id') ?? '');
    }

    public function setEntity(int $entityId): void
    {
        $this->selectedEntityId = (string) $entityId;
    }

    public function render(EntityLedgerService $ledgerService, UnitScope $unitScope)
    {
        $entityId = (int) $this->selectedEntityId;
        $rows = $entityId > 0
            ? $ledgerService->rows($entityId, $this->scopedUnitIds())
            : collect();

        $totalDebit = $rows->reduce(fn (string $c, $row): string => Money::add($c, $row->debit), Money::zero());
        $totalCredit = $rows->reduce(fn (string $c, $row): string => Money::add($c, $row->credit), Money::zero());
        $closing = $rows->last()?->runningBalance ?? Money::zero();

        return view('livewire.finance.entity-ledger-index', [
            'entities' => Entity::query()->active()->orderBy('name')->get(),
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'closing' => $closing,
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
        ]);
    }
}
