<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class EmployeeOvertime extends Model
{
    use HasFactory;

    protected $table = 'employee_overtimes';
    
    protected $fillable = [
        'resort_id',
        'Emp_id',
        'Shift_id',
        'roster_id',
        'parent_attendance_id',
        'date',
        'start_time',
        'end_time',
        'total_time',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'start_location',
        'end_location',
        'overtime_type',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }
        });
    }

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'Emp_id');
    }

    public function shift()
    {
        return $this->belongsTo(ShiftSettings::class, 'Shift_id');
    }

    public function roster()
    {
        return $this->belongsTo(DutyRoster::class, 'roster_id');
    }

    public function parentAttendance()
    {
        return $this->belongsTo(ParentAttendace::class, 'parent_attendance_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(ResortAdmin::class, 'approved_by');
    }
}
