<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'name',
    'species',
    'age',
    'origin',
    'style',
    'acquired_date',
    'water_level',
    'location',
    'substrate',
    'fertilizer',
    'main_image',
    'description',
])]
class Bonsai extends Model
{
    protected function casts(): array
    {
        return [
            'acquired_date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BonsaiEvent::class)->orderByDesc('date');
    }

    public function images(): HasMany
    {
        return $this->hasMany(BonsaiImage::class)->orderBy('date');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class)->orderByDesc('date');
    }

    public function calendarTasks(): HasMany
    {
        return $this->hasMany(CalendarTask::class)->orderBy('date');
    }
}
