<?php

namespace App\Livewire\Concerns;

use App\Support\DateRange;
use App\Support\UnitScope;
use Livewire\Attributes\On;

trait WithDateRange
{
    public string $rangePreset = 'ytd';

    public string $rangeFrom = '';

    public string $rangeTo = '';

    public function setRangePreset(string $preset): void
    {
        $this->rangePreset = $preset;

        if (in_array('Livewire\\WithPagination', class_uses_recursive(static::class), true)) {
            $this->resetPage();
        }
    }

    public function updatedRangeFrom(): void
    {
        $this->rangePreset = 'custom';

        if (in_array('Livewire\\WithPagination', class_uses_recursive(static::class), true)) {
            $this->resetPage();
        }
    }

    public function updatedRangeTo(): void
    {
        $this->rangePreset = 'custom';

        if (in_array('Livewire\\WithPagination', class_uses_recursive(static::class), true)) {
            $this->resetPage();
        }
    }

    protected function dateRange(): DateRange
    {
        return DateRange::fromState(
            $this->rangePreset,
            $this->rangeFrom ?: null,
            $this->rangeTo ?: null,
        );
    }
}

trait WithUnitScopeRefresh
{
    #[On('units-changed')]
    public function refreshUnitScope(): void
    {
        if (in_array('Livewire\\WithPagination', class_uses_recursive(static::class), true)) {
            $this->resetPage();
        }
    }

    /**
     * @return list<int>
     */
    protected function scopedUnitIds(): array
    {
        return app(UnitScope::class)->selectedUnitIds();
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
