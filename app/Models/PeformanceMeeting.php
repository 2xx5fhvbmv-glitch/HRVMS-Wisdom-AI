<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeformanceMeeting extends Model
{
    use HasFactory;

    protected $table = 'peformance_meetings';
    protected $fillable = [
        'title',	'start_time','end_time','date','location','conference_links','description','resort_id'
    ];
}
