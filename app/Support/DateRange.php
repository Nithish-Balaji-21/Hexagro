<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class DateRange
{
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
            '7d' => new self('7d', $asOf->copy()->subDays(6)->toDateString(), $latest),
            '1m' => new self('1m', $asOf->copy()->subMonth()->addDay()->toDateString(), $latest),
            'custom' => new self('custom', $from ?: $fyStart, $to ?: $latest),
            default => new self('ytd', $fyStart, $latest),
        };
    }

    public function label(): string
    {
        return match ($this->preset) {
            '7d' => 'Last 7 days',
            '1m' => 'Last 1 month',
            'custom' => ($this->from && $this->to)
                ? Inr::formatDate($this->from).' – '.Inr::formatDate($this->to)
                : 'Pick a date range',
            default => 'YTD',
        };
    }

    public function containsDate(string $date): bool
    {
        return ($this->from === null || $date >= $this->from)
            && ($this->to === null || $date <= $this->to);
    }
}
