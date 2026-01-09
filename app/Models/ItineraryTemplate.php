<?php
// app/Models/ItineraryTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryTemplate extends Model
{
    protected $fillable = ['resort_id', 'name', 'template_type', 'description','fields'];

    protected $casts = [
        'fields' => 'array',
    ];
}
