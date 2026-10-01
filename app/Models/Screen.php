<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Screen extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'short_code',
        'last_ping_at',
        'default_slide_duration',
        'transition_effect',
        'orientation',
        'resolution_hint',
    ];

    protected $casts = [
        'last_ping_at' => 'datetime',
        'default_slide_duration' => 'integer',
    ];

    protected $appends = [
        'status',
        'is_online',
        'public_url',
        'short_url',
        'local_url',
    ];

    public static function generateNextShortCode(): string
    {
        $numericCodes = static::whereNotNull('short_code')
            ->pluck('short_code')
            ->filter(fn($val) => is_numeric($val))
            ->map(fn($val) => (int)$val);

        $next = $numericCodes->isEmpty() ? 1 : ($numericCodes->max() + 1);
        return (string)$next;
    }

    protected static function booted(): void
    {
        static::creating(function ($screen) {
            if (empty($screen->short_code)) {
                $screen->short_code = static::generateNextShortCode();
            }
        });
    }

    public function getStatusAttribute(): string
    {
        return $this->is_online ? 'online' : 'offline';
    }

    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_ping_at) {
            return false;
        }

        // Online if pinged within the last 90 seconds
        return $this->last_ping_at->gt(now()->subSeconds(90));
    }

    public function getPublicUrlAttribute(): string
    {
        $code = $this->short_code ?: $this->id;
        $displayBase = env('DISPLAY_BASE_URL');
        if ($displayBase) {
            return rtrim($displayBase, '/') . "/v/{$code}";
        }
        $root = rtrim(url('/'), '/');
        if (str_ends_with($root, '/v')) {
            return "{$root}/{$code}";
        }
        return "{$root}/v/{$code}";
    }

    public function getShortUrlAttribute(): string
    {
        $domain = \App\Models\AppSetting::get('display_domain') ?: env('DISPLAY_DOMAIN', 'trotiluxe.ma');
        $cleanDomain = preg_replace('#^https?://#', '', rtrim($domain, '/'));
        $code = $this->short_code ?: '1';
        if (str_ends_with($cleanDomain, '/v')) {
            return "{$cleanDomain}/{$code}";
        }
        return "{$cleanDomain}/v/{$code}";
    }

    public function getLocalUrlAttribute(): string
    {
        return $this->getPublicUrlAttribute();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('display_order', 'asc');
    }

    public function activeSlides(): HasMany
    {
        return $this->hasMany(Slide::class)
            ->where('active', true)
            ->orderBy('display_order', 'asc');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ScreenSetting::class);
    }

    public function slidePlays(): HasMany
    {
        return $this->hasMany(SlidePlay::class);
    }
}
