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

    /** @var list<string> */
    public array $selectedUnits = [];

    /** @var list<int> */
    public array $selectedIds = [];

    public bool $locked = false;

    public function mount(UnitScope $unitScope): void
    {
        $this->visibleUnits = $unitScope->visibleUnits();
        $this->refreshSelection($unitScope);
        $this->locked = $this->visibleUnits->count() <= 1;
    }

    public function selectAll(UnitScope $unitScope): void
    {
        if ($this->locked || $this->visibleUnits->count() < 2) {
            return;
        }

        $unitScope->setSelectedUnitNames($this->visibleUnits->pluck('name')->all());
        $this->refreshSelection($unitScope);
        $this->dispatchEvents();
    }

    public function toggleUnit(string|int $unit, UnitScope $unitScope): void
    {
        if ($this->locked) {
            return;
        }

        $targetUnit = is_numeric($unit)
            ? $this->visibleUnits->firstWhere('id', (int) $unit)
            : $this->visibleUnits->firstWhere('name', (string) $unit);

        if (! $targetUnit) {
            return;
        }

        $unitName = $targetUnit->name;
        $selectedNames = $this->selectedUnits;

        if (in_array($unitName, $selectedNames, true)) {
            // Guard: Refuse to deselect if it's the last remaining selected unit
            if (count($selectedNames) <= 1) {
                return;
            }

            // Remove unit from selection
            $selectedNames = array_values(array_filter(
                $selectedNames,
                fn (string $name): bool => $name !== $unitName,
            ));
        } else {
            // Add unit to selection
            $selectedNames[] = $unitName;
        }

        $unitScope->setSelectedUnitNames($selectedNames);
        $this->refreshSelection($unitScope);
        $this->dispatchEvents();
    }

    public function isAllSelected(): bool
    {
        return count($this->selectedUnits) === $this->visibleUnits->count();
    }

    public function isUnitSelected(string|int $unit): bool
    {
        if (is_numeric($unit)) {
            return in_array((int) $unit, $this->selectedIds, true);
        }

        return in_array((string) $unit, $this->selectedUnits, true);
    }

    private function refreshSelection(UnitScope $unitScope): void
    {
        $this->selectedUnits = $unitScope->selectedUnitNames();
        $this->selectedIds = $unitScope->selectedUnitIds();
    }

    private function dispatchEvents(): void
    {
        $this->dispatch('units-selection-changed');
    }

    public function render()
    {
        return view('livewire.layout.unit-switcher');
    }
}
