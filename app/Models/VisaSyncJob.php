<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaSyncJob extends Model
{
    protected $fillable = ['resort_id', 'status', 'result'];

    protected $casts = [
        'result' => 'array',
    ];
}
