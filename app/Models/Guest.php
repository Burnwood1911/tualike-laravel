<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function event(): BelongsTo
    {

        return $this->belongsTo(Event::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->guest_type === 'SINGLE') {
                $model->uses = 1;
            } elseif ($model->guest_type === 'DOUBLE') {
                $model->uses = 2;
            } else {
                $model->uses = 1;
            }
        });

    }
}
