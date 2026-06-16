<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaSyncJob extends Model
{
    protected $fillable = ['resort_id', 'status', 'xpat_task_id', 'quota_task_id', 'result'];

    protected $casts = [
        'result' => 'array',
    ];
}
