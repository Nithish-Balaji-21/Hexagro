@props([
    'preset' => 'ytd',
    'from' => '',
    'to' => '',
    'pickerOpen' => false,
    'pickerFrom' => '',
    'pickerTo' => '',
    'pickerPreset' => 'custom',
])

@php
    $isOpen = (bool) ($pickerOpen || ($rangePickerOpen ?? false));
    $pFrom = (string) ($pickerFrom ?: ($pickerFromVal ?? ($pickerFrom ?? '')));
    $pTo = (string) ($pickerTo ?: ($pickerToVal ?? ($pickerTo ?? '')));
    $pPreset = (string) ($pickerPreset ?: ($pickerPresetVal ?? ($pickerPreset ?? 'custom')));
    $range = \App\Support\DateRange::fromState($preset, $from ?: null, $to ?: null);
    $pickerRange = \App\Support\DateRange::fromState($pPreset, $pFrom ?: null, $pTo ?: null);
@endphp

<div
    {{ $attributes->merge(['class' => 'range-picker']) }}
    x-data="hexRangePicker"
>
    <div class="range-pills">
        @foreach (['ytd' => 'YTD', '7d' => '7D', '1m' => '1M', 'custom' => 'Custom'] as $key => $label)
            <button
                type="button"
                wire:click="setRangePreset('{{ $key }}')"
                class="range-pill {{ $preset === $key ? 'active' : '' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <button type="button" wire:click="openRangePicker" class="range-label-btn">
        {{ $range->label() }}
    </button>

    @if ($isOpen)
        <div class="range-popover-backdrop" wire:click="cancelRangePicker"></div>
        <div class="range-popover hex-card" @click.stop>
            <div class="range-popover-body">
                <aside class="range-sidebar">
                    @foreach (\App\Support\DateRange::SIDEBAR_PRESETS as $sidebarPreset)
                        <button
                            type="button"
                            wire:click="setPickerPreset('{{ $sidebarPreset }}')"
                            class="range-sidebar-item {{ $pPreset === $sidebarPreset ? 'active' : '' }}"
                        >
                            {{ \App\Support\DateRange::sidebarLabel($sidebarPreset) }}
                        </button>
                    @endforeach
                </aside>

                <div class="range-calendar">
                    <div class="calendar-dual-wrapper">
                        <!-- Left Month -->
                        <div class="calendar-month-box">
                            <div class="calendar-header">
                                <button type="button" @click="prevLeftMonth" class="cal-nav-btn">&lsaquo;</button>
                                <div class="cal-selects">
                                    <select x-model="leftMonth" @change="onLeftMonthChange">
                                        <template x-for="(mName, idx) in months" :key="idx">
                                            <option :value="idx" x-text="mName"></option>
                                        </template>
                                    </select>
                                    <select x-model="leftYear" @change="onLeftYearChange">
                                        <template x-for="y in years" :key="y">
                                            <option :value="y" x-text="y"></option>
                                        </template>
                                    </select>
                                </div>
                                <button type="button" @click="nextLeftMonth" class="cal-nav-btn">&rsaquo;</button>
                            </div>
                            <div class="calendar-weekdays">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="calendar-days-grid">
                                <template x-for="(cell, idx) in getMonthDays(leftYear, leftMonth)" :key="'L-' + idx">
                                    <button
                                        type="button"
                                        @click="selectDate(cell.dateStr)"
                                        :class="{
                                            'cal-day': true,
                                            'is-other': !cell.isCurrentMonth,
                                            'is-start': isStart(cell.dateStr),
                                            'is-end': isEnd(cell.dateStr),
                                            'is-in-range': isInRange(cell.dateStr)
                                        }"
                                    >
                                        <span x-text="cell.day"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Right Month -->
                        <div class="calendar-month-box">
                            <div class="calendar-header">
                                <button type="button" @click="prevRightMonth" class="cal-nav-btn">&lsaquo;</button>
                                <div class="cal-selects">
                                    <select x-model="rightMonth" @change="onRightMonthChange">
                                        <template x-for="(mName, idx) in months" :key="idx">
                                            <option :value="idx" x-text="mName"></option>
                                        </template>
                                    </select>
                                    <select x-model="rightYear" @change="onRightYearChange">
                                        <template x-for="y in years" :key="y">
                                            <option :value="y" x-text="y"></option>
                                        </template>
                                    </select>
                                </div>
                                <button type="button" @click="nextRightMonth" class="cal-nav-btn">&rsaquo;</button>
                            </div>
                            <div class="calendar-weekdays">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="calendar-days-grid">
                                <template x-for="(cell, idx) in getMonthDays(rightYear, rightMonth)" :key="'R-' + idx">
                                    <button
                                        type="button"
                                        @click="selectDate(cell.dateStr)"
                                        :class="{
                                            'cal-day': true,
                                            'is-other': !cell.isCurrentMonth,
                                            'is-start': isStart(cell.dateStr),
                                            'is-end': isEnd(cell.dateStr),
                                            'is-in-range': isInRange(cell.dateStr)
                                        }"
                                    >
                                        <span x-text="cell.day"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="range-popover-footer">
                <span class="range-popover-dates" x-text="formattedRangeLabel()"></span>
                <div class="range-popover-actions">
                    <button type="button" wire:click="cancelRangePicker" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="button" wire:click="applyRangePicker" class="btn btn-primary btn-sm">Apply</button>
                </div>
            </div>
        </div>
    @endif
</div>
