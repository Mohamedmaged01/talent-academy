<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'stat_students' => '+500',
        'stat_trainees' => '+200',
        'stat_teachers' => '+50',
        'stat_programs' => '+30',
        'stat_groups' => '+15',
        'contact_phone_1' => '01011699453',
        'contact_phone_2' => '01067979931',
        'contact_whatsapp' => '201011699453',
        'contact_email' => 'info@talent-academy.edu.eg',
        'contact_address_ar' => 'بني مزار - شارع كوبري أبو الليل - بجوار مسجد الحجة زكية - محافظة المنيا',
        'contact_address_en' => 'Beni Mazar - Abou El-Leil Bridge St. - Next to Hegga Zakia Mosque - Minya',
        'working_hours_ar' => 'السبت - الخميس: 08:00 صباحاً - 05:00 مساءً',
        'working_hours_en' => 'Saturday - Thursday: 08:00 AM - 05:00 PM',
        'map_embed_url' => 'https://maps.google.com/maps?q=28.506191906695975,30.80087936418224&hl=ar&z=16&output=embed',
        'social_facebook' => 'https://facebook.com',
        'social_youtube' => 'https://youtube.com',
        'social_instagram' => 'https://instagram.com',
        'director_name_ar' => 'أ.د. حسن هندي',
        'director_name_en' => 'Prof. Hassan Hindi',
    ];

    /**
     * Website texts and images editable from "Site content". Empty values fall
     * back to the built-in (translated) wording and the bundled images.
     */
    public const CONTENT_DEFAULTS = [
        'hero_title_ar' => null, 'hero_title_en' => null,
        'hero_tagline_ar' => null, 'hero_tagline_en' => null,
        'hero_text_ar' => null, 'hero_text_en' => null,
        'about_intro_ar' => null, 'about_intro_en' => null,
        'notice_bar_ar' => null, 'notice_bar_en' => null,
        'hero_image' => null, 'about_image' => null, 'pathway_academic_image' => null,
        'pathway_training_image' => null, 'login_image' => null,
    ];

    /** Content keys that hold a media-library path. */
    public const CONTENT_IMAGES = ['hero_image', 'about_image', 'pathway_academic_image', 'pathway_training_image', 'login_image'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /** URL of a site image chosen in "Site content", or the bundled default. */
    public static function image(string $key, string $fallback): string
    {
        $path = static::get($key);

        if (! $path) {
            return asset($fallback);
        }

        return str_starts_with($path, 'images/') || str_starts_with($path, 'http') ? asset($path) : Storage::url($path);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings.all');
    }

    /** Pick the locale-specific value for keys stored as `{key}_ar` / `{key}_en`. */
    public static function tr(string $key): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return static::get("{$key}_{$locale}") ?: static::get("{$key}_ar");
    }
}
