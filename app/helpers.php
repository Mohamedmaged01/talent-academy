<?php

use App\Models\Setting;
use App\Support\Permissions;

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

if (! function_exists('setting_image')) {
    /** A website image chosen under "Site content", or the bundled default. */
    function setting_image(string $key, string $fallback): string
    {
        return Setting::image($key, $fallback);
    }
}

if (! function_exists('whatsapp_link')) {
    /**
     * wa.me click-to-chat link with a pre-filled message. Egyptian local numbers
     * (01xxxxxxxxx) get the +20 country code. Returns null without a usable number.
     */
    function whatsapp_link(?string $phone, string $message = ''): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (preg_match('/^01\d{9}$/', $digits)) {
            $digits = '2'.$digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/'.$digits.($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}

if (! function_exists('permission_label')) {
    /** "finance.manage" → "Invoices, payments & expenses · Manage" in the current language. */
    function permission_label(string $permission): string
    {
        [$module, $action] = array_pad(explode('.', $permission, 2), 2, 'view');
        $label = Permissions::MODULES[$module] ?? $module;

        return __($label).' · '.__(ucfirst($action));
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return app()->getLocale() === 'ar';
    }
}
