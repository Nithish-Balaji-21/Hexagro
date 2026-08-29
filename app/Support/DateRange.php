<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class DateRange
{
    /** @var list<string> */
    public const SIDEBAR_PRESETS = [
        'today',
        'this_week',
        'this_month',
        'this_year',
        'yesterday',
        'last_week',
        'last_month',
        'last_year',
        'custom',
    ];

    public function __construct(
        public string $preset,
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    public static function fromState(string $preset, ?string $from = null, ?string $to = null, ?Carbon $asOf = null): self
    {
        $asOf ??= now();
        $latest = $asOf->toDateString();
        $fyStart = FiscalYear::months()[0]['start']->toDateString();

        return match ($preset) {
            'today' => new self('today', $latest, $latest),
            'yesterday' => new self('yesterday', $asOf->copy()->subDay()->toDateString(), $asOf->copy()->subDay()->toDateString()),
            'this_week' => new self('this_week', $asOf->copy()->startOfWeek(Carbon::SUNDAY)->toDateString(), $latest),
            'last_week' => new self(
                'last_week',
                $asOf->copy()->subWeek()->startOfWeek(Carbon::SUNDAY)->toDateString(),
                $asOf->copy()->subWeek()->endOfWeek(Carbon::SATURDAY)->toDateString(),
            ),
            'this_month' => new self('this_month', $asOf->copy()->startOfMonth()->toDateString(), $latest),
            'last_month' => new self(
                'last_month',
                $asOf->copy()->subMonth()->startOfMonth()->toDateString(),
                $asOf->copy()->subMonth()->endOfMonth()->toDateString(),
            ),
            'this_year' => new self('this_year', $asOf->copy()->startOfYear()->toDateString(), $latest),
            'last_year' => new self(
                'last_year',
                $asOf->copy()->subYear()->startOfYear()->toDateString(),
                $asOf->copy()->subYear()->endOfYear()->toDateString(),
            ),
            '7d' => new self('7d', $asOf->copy()->subDays(6)->toDateString(), $latest),
            '1m' => new self('1m', $asOf->copy()->subMonth()->addDay()->toDateString(), $latest),
            'custom' => new self('custom', $from ?: $fyStart, $to ?: $latest),
            default => new self('ytd', $fyStart, $latest),
        };
    }

    public function label(): string
    {
        return match ($this->preset) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This week',
            'last_week' => 'Last week',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_year' => 'This year',
            'last_year' => 'Last year',
            '7d' => 'Last 7 days',
            '1m' => 'Last 1 month',
            'custom' => ($this->from && $this->to)
                ? Inr::formatDate($this->from).' – '.Inr::formatDate($this->to)
                : 'Pick a date range',
            default => 'YTD',
        };
    }

    public function pickerLabel(): string
    {
        if ($this->from && $this->to) {
            return Inr::formatDatePicker($this->from).' - '.Inr::formatDatePicker($this->to);
        }

        return 'Pick a date range';
    }

    public function containsDate(string $date): bool
    {
        return ($this->from === null || $date >= $this->from)
            && ($this->to === null || $date <= $this->to);
    }

    public static function sidebarLabel(string $preset): string
    {
        return match ($preset) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            'custom' => 'Custom',
            default => ucfirst(str_replace('_', ' ', $preset)),
        };
    }
}
