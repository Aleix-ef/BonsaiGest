<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['bonsai_id', 'image', 'date', 'description'])]
class BonsaiImage extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function bonsai(): BelongsTo
    {
        return $this->belongsTo(Bonsai::class);
    }
}
