<?php

namespace App\Livewire\Import;

use App\Models\DebitTransaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'import', 'pageTitle' => 'Import Data'])]
#[Title('Import Data')]
class ImportIndex extends Component
{
    public function mount(): void
    {
        $this->authorize('create', DebitTransaction::class);
    }

    public function render()
    {
        return view('livewire.import.import-index');
    }
}
