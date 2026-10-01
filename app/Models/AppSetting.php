<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'app_name',
        'business_name',
        'display_domain',
        'default_slide_duration',
        'default_transition',
        'default_orientation',
        'operating_hours_enabled',
        'opening_time',
        'closing_time',
        'instagram_handle',
        'contact_email',
        'contact_phone',
        'master_pin',
        'logo_path',
    ];

    protected $casts = [
        'default_slide_duration' => 'integer',
        'operating_hours_enabled' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return '/storage/' . ltrim($this->logo_path, '/');
    }

    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'app_name' => 'AtoFood Signage',
            'business_name' => 'Trotiluxe',
            'display_domain' => 'trotiluxe.ma',
            'default_slide_duration' => 10,
            'default_transition' => 'fade',
            'default_orientation' => 'landscape',
            'operating_hours_enabled' => false,
            'opening_time' => '08:00',
            'closing_time' => '23:00',
            'instagram_handle' => '@trotiluxe',
            'contact_email' => 'contact@trotiluxe.ma',
            'master_pin' => '1234',
        ]);
    }

    public static function get(string $key, $default = null)
    {
        $setting = static::first();
        if ($setting && isset($setting->$key)) {
            return $setting->$key;
        }
        return $default;
    }
}
