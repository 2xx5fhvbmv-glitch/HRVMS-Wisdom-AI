<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['resort_id','name', 'state_id','country_id'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
