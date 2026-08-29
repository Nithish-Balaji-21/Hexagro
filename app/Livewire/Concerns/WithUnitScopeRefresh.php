<?php

namespace App\Livewire\Concerns;

use App\Models\CostCenter;
use App\Support\UnitScope;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

trait WithUnitScopeRefresh
{
    public int $unitScopeVersion = 0;

    #[On('units-selection-changed')]
    public function refreshUnitScope(): void
    {
        $this->unitScopeVersion++;
        $this->syncUnitFilters();

        if (in_array('Livewire\\WithPagination', class_uses_recursive(static::class), true)) {
            $this->resetPage();
        }
    }

    /**
     * Reset page-level unit filters when the top-bar scope changes.
     * Components with Settlement-style tabs should override this method.
     */
    protected function syncUnitFilters(): void
    {
        $unitIds = $this->scopedUnitIds();
        $single = count($unitIds) === 1 ? (string) $unitIds[0] : '';

        if (property_exists($this, 'unitTab')) {
            $this->unitTab = $single;
        }

        if (property_exists($this, 'unitFilter')) {
            $this->unitFilter = $single;
        }
    }

    /**
     * @return list<int>
     */
    protected function scopedUnitIds(): array
    {
        return app(UnitScope::class)->selectedUnitIds();
    }

    /**
     * @return list<string>
     */
    protected function scopedUnitNames(): array
    {
        return app(UnitScope::class)->selectedUnitNames();
    }

    /**
     * @return Collection<int, CostCenter>
     */
    protected function scopedUnits(): Collection
    {
        return app(UnitScope::class)->selectedUnits();
    }

    protected function scopeLabel(): string
    {
        return app(UnitScope::class)->scopeLabel();
    }

    protected function isAllUnitsSelected(): bool
    {
        return app(UnitScope::class)->isAllSelected();
    }
}
