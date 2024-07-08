<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $guarded  = [];



    public function event() {

        return $this->belongsTo(Event::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->guest_type === 'SINGLE') {
                $model->uses = 1;
            } elseif ($model->guest_type === 'DOUBLE') {
                $model->uses = 2;
            }
        });


    }


}
