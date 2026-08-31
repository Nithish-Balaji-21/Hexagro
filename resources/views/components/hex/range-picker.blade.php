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
    {{ $attributes->merge(['class' => 'range-picker']) }}
    x-data="hexRangePicker"
>
    <div class="range-bar">
        <div class="date-pill">
            <x-hex.icon name="calendar" />
            <span class="date-pill-text">{{ $range->displayLabel() }}</span>
        </div>

        <div class="picker" x-ref="picker">
            <div class="indicator" x-ref="indicator"></div>
            @foreach (\App\Support\DateRange::QUICK_PRESETS as $key)
                <button
                    type="button"
                    data-range="{{ $key }}"
                    wire:click="setRangePreset('{{ $key }}')"
                    :class="{ 'active': activeQuickPreset === '{{ $key }}' }"
                    class="{{ $quickActive === $key ? 'active' : '' }}"
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
                            class="{{ $pPreset === $key ? 'is-selected' : '' }}"
                        >
                            {{ \App\Support\DateRange::sidebarLabel($key) }}
                        </button>
                    @endforeach
                </div>

                <div class="months">
                    <div class="month">
                        <div class="month-nav">
                            <button type="button" class="navbtn" @click="prevLeftMonth">&lsaquo;</button>
                            <div class="month-year-selects">
                                <select x-model.number="leftMonth" @change="updateRightFromLeft()" class="mselect">
                                    <template x-for="(mName, idx) in monthNames" :key="'lm-' + idx">
                                        <option :value="idx" x-text="mName" :selected="leftMonth === idx"></option>
                                    </template>
                                </select>
                                <select x-model.number="leftYear" @change="updateRightFromLeft()" class="yselect">
                                    <template x-for="y in yearOptions" :key="'ly-' + y">
                                        <option :value="y" x-text="y" :selected="leftYear === y"></option>
                                    </template>
                                </select>
                            </div>
                            <span class="navbtn hidden">&rsaquo;</span>
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
                                                    @click="! cell.muted && selectDate(cell.dateStr)"
                                                    @mouseenter="! cell.muted && previewDate(cell.dateStr)"
                                                    @mouseleave="clearPreview()"
                                                    :class="cell.muted ? 'day muted' : dayClasses(cell.dateStr)"
                                                    :disabled="cell.muted"
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
                            <span class="navbtn hidden">&lsaquo;</span>
                            <div class="month-year-selects">
                                <select x-model.number="rightMonth" @change="updateLeftFromRight()" class="mselect">
                                    <template x-for="(mName, idx) in monthNames" :key="'rm-' + idx">
                                        <option :value="idx" x-text="mName" :selected="rightMonth === idx"></option>
                                    </template>
                                </select>
                                <select x-model.number="rightYear" @change="updateLeftFromRight()" class="yselect">
                                    <template x-for="y in yearOptions" :key="'ry-' + y">
                                        <option :value="y" x-text="y" :selected="rightYear === y"></option>
                                    </template>
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
                                                    @click="! cell.muted && selectDate(cell.dateStr)"
                                                    @mouseenter="! cell.muted && previewDate(cell.dateStr)"
                                                    @mouseleave="clearPreview()"
                                                    :class="cell.muted ? 'day muted' : dayClasses(cell.dateStr)"
                                                    :disabled="cell.muted"
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
