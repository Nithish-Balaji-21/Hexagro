<?php

namespace App\Support;

final class Money
{
    public const SCALE = 6;

    public static function of(string|int|float $value): string
    {
        if (is_float($value)) {
            return number_format($value, self::SCALE, '.', '');
        }

        return bcadd((string) $value, '0', self::SCALE);
    }

    public static function add(string|int|float $left, string|int|float $right, int $scale = self::SCALE): string
    {
        return bcadd(self::of($left), self::of($right), $scale);
    }

    public static function sub(string|int|float $left, string|int|float $right, int $scale = self::SCALE): string
    {
        return bcsub(self::of($left), self::of($right), $scale);
    }

    public static function mul(string|int|float $left, string|int|float $right, int $scale = self::SCALE): string
    {
        return bcmul(self::of($left), self::of($right), $scale);
    }

    public static function div(string|int|float $left, string|int|float $right, int $scale = self::SCALE): string
    {
        $divisor = self::of($right);

        if (bccomp($divisor, '0', $scale) === 0) {
            return self::of(0);
        }

        return bcdiv(self::of($left), $divisor, $scale);
    }

    public static function round(string|int|float $value, int $scale = 2): string
    {
        $normalized = self::of($value);
        $pad = str_repeat('0', $scale);
        $half = '0.'.$pad.'5';

        if (str_starts_with($normalized, '-')) {
            return bcsub($normalized, $half, $scale);
        }

        return bcadd($normalized, $half, $scale);
    }

    public static function cmp(string|int|float $left, string|int|float $right, int $scale = self::SCALE): int
    {
        return bccomp(self::of($left), self::of($right), $scale);
    }

    public static function abs(string|int|float $value): string
    {
        $normalized = self::of($value);

        if (str_starts_with($normalized, '-')) {
            return substr($normalized, 1);
        }

        return $normalized;
    }

    public static function zero(): string
    {
        return self::of(0);
    }
}
