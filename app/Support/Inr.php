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

        $value = (float) $amount;

        if (! is_finite($value)) {
            return '—';
        }

        $negative = $value < 0;
        $value = abs($value);

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
}
