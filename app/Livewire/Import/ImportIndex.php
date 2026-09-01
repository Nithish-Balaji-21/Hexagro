<?php

namespace App\Livewire\Import;

use App\Models\DebitTransaction;
use App\Models\ImportRun;
use App\Services\Import\ImportRunService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

use Livewire\Attributes\Url;

#[Layout('components.layouts.shell', ['currentPage' => 'import', 'pageTitle' => 'Import Data'])]
#[Title('Import Data')]
class ImportIndex extends Component
{
    use WithPagination;

    #[Url]
    public ?string $kind = null;

    public bool $showDetail = false;

    public ?ImportRun $selectedRun = null;

    public function mount(): void
    {
        $this->authorize('create', DebitTransaction::class);
    }

    public function revertRun(int $runId, ImportRunService $importRunService): void
    {
        $this->authorize('create', DebitTransaction::class);

        $run = ImportRun::query()->findOrFail($runId);
        $deleted = $importRunService->revert($run);
        
        $this->dispatch('toast', message: "Reverted import of {$run->filename} — {$deleted} row(s) removed.");
    }

    public function showRunDetails(int $runId): void
    {
        $this->authorize('create', DebitTransaction::class);

        $run = ImportRun::query()->findOrFail($runId);
        $this->selectedRun = $run->importedRecordsWithRelations();
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedRun = null;
    }

    public function render()
    {
        $query = ImportRun::query();

        if ($this->kind) {
            $query->where('kind', $this->kind);
        }

        $runs = $query->with('user')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.import.import-index', [
            'runs' => $runs,
        ]);
    }
}
