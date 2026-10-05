<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResortDataImport extends Model
{
    protected $fillable = ['resort_id', 'status', 'files', 'options', 'report', 'job_action', 'job_status', 'job_message',
        'job_started_at', 'credentials', 'created_by', 'imported_at'];

    protected $casts = [
        'files'          => 'array',
        'options'        => 'array',
        'report'         => 'array',
        'credentials'    => 'encrypted:array',
        'job_started_at' => 'datetime',
        'imported_at'    => 'datetime',
    ];

    protected $hidden = ['credentials'];

    public function busy(): bool
    {
        return in_array($this->job_status, ['queued', 'running'], true);
    }
}
