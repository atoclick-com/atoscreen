<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlidePlay extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'screen_id',
        'slide_id',
        'played_at',
        'duration_seconds',
    ];

    protected $casts = [
        'played_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function slide(): BelongsTo
    {
        return $this->belongsTo(Slide::class);
    }
}
