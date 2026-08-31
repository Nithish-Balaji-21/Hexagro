<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class Inr
{
    public static function format(string|int|float|null $amount, int $decimals = 0): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $value = round(abs((float) $amount));

        if (! is_finite($value)) {
            return '—';
        }

        $negative = (float) $amount < 0;
        $decimals = 0;

        $formatted = number_format($value, $decimals, '.', '');
        [$intPart, $decPart] = array_pad(explode('.', $formatted, 2), 2, '');

        $lastThree = substr($intPart, -3);
        $rest = substr($intPart, 0, -3);

        if ($rest !== '') {
            $lastThree = ','.$lastThree;
        }

        $grouped = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).$lastThree;
        $output = '₹'.$grouped.($decimals > 0 && $decPart !== '' ? '.'.$decPart : '');

        return $negative ? '−'.$output : $output;
    }

    public static function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d M Y');
    }

    public static function formatDatePicker(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    public static function formatDateShort(string $date): string
    {
        return Carbon::parse($date)->format('M j, Y');
    }
}
