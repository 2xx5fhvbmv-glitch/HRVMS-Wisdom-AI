<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceMeetingParticipant extends Model
{
    protected $table = 'performance_meeting_participants';

    protected $fillable = [
        'meeting_id', 'employee_id', 'resort_id', 'status', 'reason', 'token', 'responded_at'
    ];

    public function meeting()
    {
        return $this->belongsTo(PeformanceMeeting::class, 'meeting_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
