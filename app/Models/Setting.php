<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
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
