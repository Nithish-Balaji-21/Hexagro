<?php

namespace App\Livewire\Concerns;

use App\Support\DateRange;

trait WithDateRange
{
    public string $rangePreset = 'ytd';

    public string $rangeFrom = '';

    public string $rangeTo = '';

    public bool $rangePickerOpen = false;

    public string $pickerFrom = '';

    public string $pickerTo = '';

    public string $pickerPreset = 'custom';

    public function setRangePreset(string $preset): void
    {
        if ($preset === 'custom') {
            $this->openRangePicker();

            return;
        }

        $range = DateRange::fromState($preset);
        $this->rangePreset = $preset;
        $this->rangeFrom = $range->from ?? '';
        $this->rangeTo = $range->to ?? '';
        $this->pickerFrom = $this->rangeFrom;
        $this->pickerTo = $this->rangeTo;
        $this->pickerPreset = $preset;
        $this->rangePickerOpen = false;

        $this->dispatch('pulse:range-changed', [
            'from' => $this->rangeFrom,
            'to' => $this->rangeTo,
            'preset' => $this->rangePreset,
            'label' => $range->displayLabel(),
        ]);

        $this->resetDateRangePage();
    }

    public function openRangePicker(): void
    {
        $range = $this->dateRange();
        $this->pickerFrom = $range->from ?? '';
        $this->pickerTo = $range->to ?? '';
        $knownPresets = array_merge(
            array_filter(DateRange::QUICK_PRESETS, fn (string $p): bool => $p !== 'custom'),
            DateRange::SIDEBAR_PRESETS,
        );
        $this->pickerPreset = in_array($range->preset, $knownPresets, true)
            ? $range->preset
            : 'custom';
        $this->rangePickerOpen = true;
    }

    public function setPickerPreset(string $preset): void
    {
        if ($preset === 'custom') {
            $this->pickerPreset = 'custom';
            $this->pickerFrom = '';
            $this->pickerTo = '';
            $this->dispatch('range-picker-dates', from: '', to: '');

            return;
        }

        $range = DateRange::fromState($preset);
        $this->pickerPreset = $preset;
        $this->pickerFrom = $range->from ?? '';
        $this->pickerTo = $range->to ?? '';
        $this->dispatch('range-picker-dates', from: $this->pickerFrom, to: $this->pickerTo);
    }

    public function updatePickerDates(string $from, string $to): void
    {
        $this->pickerFrom = $from;
        $this->pickerTo = $to;
        $this->pickerPreset = DateRange::detectPreset($from, $to);
    }

    public function applyRangePicker(): void
    {
        if ($this->pickerPreset !== 'custom') {
            $this->rangePreset = $this->pickerPreset;
            $range = DateRange::fromState($this->pickerPreset);
            $this->rangeFrom = $range->from ?? '';
            $this->rangeTo = $range->to ?? '';
        } else {
            $this->rangePreset = 'custom';
            $this->rangeFrom = $this->pickerFrom;
            $this->rangeTo = $this->pickerTo;
        }

        $currentRange = $this->dateRange();
        $this->dispatch('pulse:range-changed', [
            'from' => $currentRange->from,
            'to' => $currentRange->to,
            'preset' => $currentRange->preset,
            'label' => $currentRange->displayLabel(),
        ]);

        $this->rangePickerOpen = false;
        $this->resetDateRangePage();
    }

    public function cancelRangePicker(): void
    {
        $this->rangePickerOpen = false;
    }

    public function updatedRangeFrom(): void
    {
        $this->rangePreset = 'custom';
        $this->resetDateRangePage();
    }

    public function updatedRangeTo(): void
    {
        $this->rangePreset = 'custom';
        $this->resetDateRangePage();
    }

    protected function dateRange(): DateRange
    {
        return DateRange::fromState(
            $this->rangePreset,
            $this->rangeFrom ?: null,
            $this->rangeTo ?: null,
        );
    }

    protected function resetDateRangePage(): void
    {
        if (in_array('Livewire\\WithPagination', class_uses_recursive(static::class), true)) {
            $this->resetPage();
        }
    }
}
