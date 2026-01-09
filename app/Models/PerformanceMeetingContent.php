<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceMeetingContent extends Model
{
    use HasFactory;

    protected $table = 'performance_meeting_contents';
    protected $fillable = [
        'resort_id','content'
    ];
}
