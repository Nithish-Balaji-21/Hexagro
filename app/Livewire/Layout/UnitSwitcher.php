<?php

namespace App\Livewire\Layout;

use App\Models\CostCenter;
use App\Support\UnitScope;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class UnitSwitcher extends Component
{
    /** @var list<string> */
    public array $selectedUnits = [];

    /** @var list<int> */
    public array $selectedIds = [];

    public function mount(UnitScope $unitScope): void
    {
        $this->refreshSelection($unitScope);
    }

    /**
     * @return Collection<int, CostCenter>
     */
    #[Computed]
    public function visibleUnits(): Collection
    {
        return app(UnitScope::class)->visibleUnits();
    }

    #[Computed]
    public function locked(): bool
    {
        return $this->visibleUnits()->count() <= 1;
    }

    public function selectAll(UnitScope $unitScope): void
    {
        if ($this->locked() || $this->visibleUnits()->count() < 2) {
            return;
        }

        $unitScope->setSelectedUnitNames($this->visibleUnits()->pluck('name')->all());
        $this->refreshSelection($unitScope);
        $this->dispatchEvents();
    }

    public function toggleUnit(string|int $unit, UnitScope $unitScope): void
    {
        if ($this->locked()) {
            return;
        }

        $visibleUnits = $this->visibleUnits();

        $targetUnit = is_numeric($unit)
            ? $visibleUnits->firstWhere('id', (int) $unit)
            : $visibleUnits->firstWhere('name', (string) $unit);

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
        return count($this->selectedUnits) === $this->visibleUnits()->count();
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
        $payload = [
            'selectedUnits' => $this->selectedUnits,
            'selectedIds' => $this->selectedIds,
        ];

        $this->dispatch('UnitsSelectionChanged', ...$payload);
        $this->dispatch('units-changed', ...$payload);
        $this->dispatch('unitsChanged', ...$payload);
        $this->dispatch('units-selection-changed', ...$payload);
    }

    public function render()
    {
        return view('livewire.layout.unit-switcher', [
            'visibleUnits' => $this->visibleUnits(),
            'locked' => $this->locked(),
            'allSelected' => $this->isAllSelected(),
        ]);
    }
}
