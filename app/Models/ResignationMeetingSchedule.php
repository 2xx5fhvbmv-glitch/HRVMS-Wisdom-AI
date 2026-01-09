<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class ResignationMeetingSchedule extends Model
{
    use HasFactory;
    protected $table = 'resignation_meeting_schedule';
    protected $fillable = [
        'resignationId','title', 'meeting_date', 'meeting_time', 'meeting_with', 'status', 'created_by'
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public static function boot(){
        parent::boot();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function resignation()
    {
        return $this->belongsTo(EmployeeResignation::class, 'resignationId');
    }
}