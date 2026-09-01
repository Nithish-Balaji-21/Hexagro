<?php

namespace App\Livewire\Transactions;

use App\Livewire\Concerns\WithImportRefresh;
use App\Livewire\Concerns\WithOutstandingBatches;
use App\Livewire\Concerns\WithUnitScopeRefresh;
use App\Support\UnitScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shell', ['currentPage' => 'receivables', 'pageTitle' => 'Receivables'])]
#[Title('Receivables')]
class ReceivablesIndex extends Component
{
    use WithImportRefresh;
    use WithOutstandingBatches;
    use WithPagination;
    use WithUnitScopeRefresh;

    protected function outstandingKind(): string
    {
        return 'receivable';
    }

    protected function outstandingPageTitle(): string
    {
        return 'Receivables';
    }

    protected function outstandingBatchHeading(): string
    {
        return 'Outstanding Receivables';
    }

    public function render(UnitScope $unitScope)
    {
        return view('livewire.transactions.outstanding-batches-index', [
            'batches' => $this->selectedBatchId === null && ! $this->showBatchForm ? $this->batches() : null,
            'recentBatches' => $this->recentBatches(),
            'batch' => $this->selectedBatch(),
            'lineTotal' => $this->computedLineTotal(),
            'scopedUnits' => $this->scopedUnits(),
            'scopeLabel' => $unitScope->scopeLabel(),
            'allSelected' => $unitScope->isAllSelected(),
            'pageTitle' => $this->outstandingPageTitle(),
            'batchHeading' => $this->outstandingBatchHeading(),
            'batchNote' => $this->outstandingBatchNote(),
        ]);
    }
}
