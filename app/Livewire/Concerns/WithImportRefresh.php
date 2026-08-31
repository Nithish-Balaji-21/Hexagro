<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

trait WithImportRefresh
{
    public int $importRefreshVersion = 0;

    #[On('import-completed')]
    public function refreshAfterImport(): void
    {
        $this->importRefreshVersion++;
    }
}
