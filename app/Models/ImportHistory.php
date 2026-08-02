<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    use HasFactory;

    protected $table = 'import_history';

    protected $fillable = [
        'resort_id',
        'module',
        'file_name',
        'status',
        'total_rows',
        'created_count',
        'updated_count',
        'error_report',
        'failure_message',
        'created_by',
    ];

    protected $casts = [
        'error_report' => 'array',
    ];
}
