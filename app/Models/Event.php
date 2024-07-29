<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function card(): BelongsTo
    {

        return $this->belongsTo(Card::class);
    }

    public function guests(): HasMany
    {

        return $this->hasMany(Guest::class);
    }
}
