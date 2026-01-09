<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'resort_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function resort()
    {
        return $this->belongsTo(Resort::class);
    }
}
