<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'type',
        'file_path',
        'content',
        'title',
        'display_order',
        'duration_override',
        'audio_enabled',
        'fit_mode',
        'active',
        'start_date',
        'end_date',
        'day_of_week_schedule',
    ];

    protected $casts = [
        'content' => 'array',
        'active' => 'boolean',
        'audio_enabled' => 'boolean',
        'display_order' => 'integer',
        'duration_override' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'day_of_week_schedule' => 'array',
    ];

    protected $appends = [
        'file_url',
        'is_in_schedule',
    ];

    protected $touches = ['screen'];

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $cleanPath = ltrim($this->file_path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        return url('storage/' . $cleanPath);
    }

    public function getIsInScheduleAttribute(): bool
    {
        if (!$this->active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date->copy()->startOfDay())) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date->copy()->endOfDay())) {
            return false;
        }

        // Day of week check: 0 (Sun) to 6 (Sat)
        if (!empty($this->day_of_week_schedule) && is_array($this->day_of_week_schedule)) {
            $currentDay = $now->dayOfWeek; // Carbon 0 (Sun) - 6 (Sat)
            $allowedDays = array_map('intval', $this->day_of_week_schedule);
            if (!in_array($currentDay, $allowedDays, true)) {
                return false;
            }
        }

        return true;
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function slidePlays(): HasMany
    {
        return $this->hasMany(SlidePlay::class);
    }
}
