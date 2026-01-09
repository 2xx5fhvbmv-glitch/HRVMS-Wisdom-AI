<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildSOSHistoryStatus extends Model
{
    use HasFactory;
    protected $table = 'child_sos_history_status';

    protected $fillable = [
        'sos_history_id',
        'sos_status',
        'created_at',
        'updated_at',
    ];
}
