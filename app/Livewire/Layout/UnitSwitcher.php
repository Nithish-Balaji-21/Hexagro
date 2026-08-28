<?php

namespace App\Livewire\Layout;

use App\Models\CostCenter;
use App\Support\UnitScope;
use Illuminate\Support\Collection;
use Livewire\Component;

class UnitSwitcher extends Component
{
    /** @var Collection<int, CostCenter> */
    public Collection $visibleUnits;

    /** @var list<int> */
    public array $selectedIds = [];

    public bool $locked = false;

    public function mount(UnitScope $unitScope): void
    {
        $this->visibleUnits = $unitScope->visibleUnits();
        $this->selectedIds = $unitScope->selectedUnitIds();
        $this->locked = $this->visibleUnits->count() <= 1;
    }

    public function selectAll(UnitScope $unitScope): void
    {
        if ($this->locked || $this->visibleUnits->count() < 2) {
            return;
        }

        $unitScope->setSelectedUnits($this->visibleUnits->pluck('id')->all());
        $this->refreshSelection($unitScope);
        $this->dispatch('units-changed');
    }

    public function toggleUnit(int $unitId, UnitScope $unitScope): void
    {
        if ($this->locked || ! $this->visibleUnits->contains('id', $unitId)) {
            return;
        }

        $selected = $this->selectedIds;

        if (in_array($unitId, $selected, true)) {
            if (count($selected) === 1) {
                return;
            }

            $selected = array_values(array_filter($selected, fn (int $id): bool => $id !== $unitId));
        } else {
            $selected[] = $unitId;
        }

        $unitScope->setSelectedUnits($selected);
        $this->refreshSelection($unitScope);
        $this->dispatch('units-changed');
    }

    public function isAllSelected(): bool
    {
        return count($this->selectedIds) === $this->visibleUnits->count();
    }

    private function refreshSelection(UnitScope $unitScope): void
    {
        $this->selectedIds = $unitScope->selectedUnitIds();
    }

    public function render()
    {
        return view('livewire.layout.unit-switcher');
    }
}
