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
    $quickActive = $isOpen || ! in_array($preset, ['7d', '1m', 'ytd'], true) ? 'custom' : $preset;
@endphp

<div
    {{ $attributes->merge(['class' => 'range-picker pulse-range-bar']) }}
    style="--pulse-range-active: var(--primary);"
    x-data="hexRangePicker"
>
    <div class="range-bar">
        <button type="button" x-ref="trigger" class="pulse-range-trigger date-pill" wire:click="openRangePicker" aria-label="Change date range">
            <span class="pulse-range-icon" aria-hidden="true">
                <x-hex.icon name="calendar" />
            </span>
            <span class="pulse-range-label date-pill-text" id="selected-range">{{ $range->displayLabel() }}</span>
        </button>

        <div class="picker pulse-range-presets" x-ref="picker" role="group" aria-label="Quick date ranges">
            <div class="indicator" x-ref="indicator"></div>
            @foreach (\App\Support\DateRange::QUICK_PRESETS as $key)
                <button
                    type="button"
                    data-range="{{ $key }}"
                    data-range-preset="{{ $key }}"
                    wire:click="setRangePreset('{{ $key }}')"
                    :class="{ 'active is-active': activeQuickPreset === '{{ $key }}' }"
                    class="pulse-range-preset {{ $quickActive === $key ? 'active is-active' : '' }}"
                    aria-pressed="{{ $quickActive === $key ? 'true' : 'false' }}"
                >
                    {{ \App\Support\DateRange::quickPresetLabel($key) }}
                </button>
            @endforeach
        </div>

        @if ($isOpen)
            <div class="range-panel-backdrop" wire:click="cancelRangePicker"></div>
            <div class="panel open" @click.stop>
                <div class="panel-head" x-text="panelHeadText"></div>

                <div class="presets-row">
                    @foreach (\App\Support\DateRange::SIDEBAR_PRESETS as $key)
                        <button
                            type="button"
                            wire:click="setPickerPreset('{{ $key }}')"
                            :class="{ 'is-selected': activeSidebarPreset === '{{ $key }}' }"
                            class="{{ $pPreset === $key ? 'is-selected' : '' }}"
                        >
                            {{ \App\Support\DateRange::sidebarLabel($key) }}
                        </button>
                    @endforeach
                </div>

@php
    $monthsList = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $currentYearVal = (int) date('Y');
    $yearsList = range($currentYearVal - 6, $currentYearVal);
@endphp

                <div class="months">
                    <div class="month">
                        <div class="month-nav">
                            <button type="button" class="navbtn" @click="prevLeftMonth">&lsaquo;</button>
                            <div class="month-year-selects">
                                <select x-model.number="leftMonth" @change="onLeftMonthChange" class="mselect">
                                    @foreach ($monthsList as $idx => $mName)
                                        <option value="{{ $idx }}">{{ $mName }}</option>
                                    @endforeach
                                </select>
                                <select x-model.number="leftYear" @change="onLeftYearChange" class="yselect">
                                    @foreach ($yearsList as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="navbtn" @click="nextLeftMonth">&rsaquo;</button>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <template x-for="dow in dowLabels" :key="dow">
                                        <th x-text="dow"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(week, wIdx) in leftWeeks" :key="'L-' + wIdx">
                                    <tr>
                                        <template x-for="(cell, cIdx) in week" :key="'L-' + wIdx + '-' + cIdx">
                                            <td>
                                                <button
                                                    type="button"
                                                    @click="! isDisabled(cell.dateStr, cell.muted) && selectDate(cell.dateStr)"
                                                    @mouseenter="! isDisabled(cell.dateStr, cell.muted) && previewDate(cell.dateStr)"
                                                    @mouseleave="clearPreview()"
                                                    :class="dayClasses(cell)"
                                                    :disabled="isDisabled(cell.dateStr, cell.muted)"
                                                    x-text="cell.day"
                                                ></button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="month">
                        <div class="month-nav">
                            <button type="button" class="navbtn" @click="prevRightMonth">&lsaquo;</button>
                            <div class="month-year-selects">
                                <select x-model.number="rightMonth" @change="onRightMonthChange" class="mselect">
                                    @foreach ($monthsList as $idx => $mName)
                                        <option value="{{ $idx }}">{{ $mName }}</option>
                                    @endforeach
                                </select>
                                <select x-model.number="rightYear" @change="onRightYearChange" class="yselect">
                                    @foreach ($yearsList as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="navbtn" @click="nextRightMonth">&rsaquo;</button>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <template x-for="dow in dowLabels" :key="'R-' + dow">
                                        <th x-text="dow"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(week, wIdx) in rightWeeks" :key="'R-' + wIdx">
                                    <tr>
                                        <template x-for="(cell, cIdx) in week" :key="'R-' + wIdx + '-' + cIdx">
                                            <td>
                                                <button
                                                    type="button"
                                                    @click="! isDisabled(cell.dateStr, cell.muted) && selectDate(cell.dateStr)"
                                                    @mouseenter="! isDisabled(cell.dateStr, cell.muted) && previewDate(cell.dateStr)"
                                                    @mouseleave="clearPreview()"
                                                    :class="dayClasses(cell)"
                                                    :disabled="isDisabled(cell.dateStr, cell.muted)"
                                                    x-text="cell.day"
                                                ></button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel-foot">
                    <div class="hint" x-text="hintText"></div>
                    <div class="foot-actions">
                        <button type="button" wire:click="cancelRangePicker" class="btn-cancel">Cancel</button>
                        <button type="button" wire:click="applyRangePicker" class="btn-apply">Apply</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
