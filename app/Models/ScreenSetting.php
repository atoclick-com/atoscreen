<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ScreenSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'logo_overlay_enabled',
        'logo_path',
        'logo_position',
        'accent_color',
        'audio_enabled',
        'audio_volume',
        'aspect_ratio_mode',
        'ticker_enabled',
        'ticker_text',
        'ticker_speed',
        'clock_widget_enabled',
        'clock_format',
        'weather_widget_enabled',
        'weather_city',
        'offline_fallback',
        'auto_refresh_interval',
        'operating_hours_enabled',
        'opening_time',
        'closing_time',
        'instagram_handle',
        'screen_pin',
    ];

    protected $casts = [
        'logo_overlay_enabled' => 'boolean',
        'audio_enabled' => 'boolean',
        'audio_volume' => 'integer',
        'operating_hours_enabled' => 'boolean',
        'ticker_enabled' => 'boolean',
        'ticker_speed' => 'integer',
        'clock_widget_enabled' => 'boolean',
        'weather_widget_enabled' => 'boolean',
        'auto_refresh_interval' => 'integer',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected $touches = ['screen'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        $cleanPath = ltrim($this->logo_path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        return url('storage/' . $cleanPath);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }
}
