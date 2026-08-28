<?php

namespace App\Livewire;

use App\Support\UnitScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shell', ['currentPage' => 'dashboard', 'pageTitle' => 'Dashboard'])]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $scopeLabel = '';

    /** @var list<string> */
    public array $selectedUnitNames = [];

    public function mount(UnitScope $unitScope): void
    {
        $this->scopeLabel = $unitScope->scopeLabel();
        $this->selectedUnitNames = $unitScope->selectedUnits()->pluck('name')->all();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
