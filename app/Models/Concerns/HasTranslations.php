<?php

namespace App\Models\Concerns;

/**
 * Bilingual columns are stored as `{attribute}_ar` / `{attribute}_en`.
 * `$model->tr('title')` returns the value for the active locale and falls
 * back to the other language when the current one is empty.
 */
trait HasTranslations
{
    public function tr(string $attribute): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $fallback = $locale === 'ar' ? 'en' : 'ar';

        $value = $this->getAttribute("{$attribute}_{$locale}");

        return filled($value) ? $value : $this->getAttribute("{$attribute}_{$fallback}");
    }
}
