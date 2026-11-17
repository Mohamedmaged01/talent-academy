<?php

if (! function_exists('money')) {
    /** Format an amount in Egyptian pounds, e.g. "12,500.00 ج.م" / "EGP 12,500.00". */
    function money(float|int|string|null $amount, bool $decimals = true): string
    {
        $formatted = number_format((float) $amount, $decimals ? 2 : 0);

        return app()->getLocale() === 'ar' ? $formatted.' ج.م' : 'EGP '.$formatted;
    }
}

if (! function_exists('pct')) {
    function pct(?float $value): string
    {
        return $value === null ? '—' : rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return app()->getLocale() === 'ar';
    }
}
