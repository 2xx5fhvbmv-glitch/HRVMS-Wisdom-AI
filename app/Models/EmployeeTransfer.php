<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;


class EmployeeTransfer extends Model
{
    use HasFactory;
    protected $table = 'employee_transfers';

    protected $fillable = [
        'resort_id','employee_id','current_department_id','target_department_id',
        'current_position_id','target_position_id','reason_for_transfer','effective_date',
        'transfer_status','additional_notes','status','letter_dispatched','reporting_manager','created_by','modified_by'
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function currentDepartment() {
        return $this->belongsTo(ResortDepartment::class, 'current_department_id');
    }
    
    public function targetDepartment() {
        return $this->belongsTo(ResortDepartment::class, 'target_department_id');
    } 
    
    public function currentPosition() {
        return $this->belongsTo(ResortPosition::class, 'current_position_id');
    }
    
    public function targetPosition() {
        return $this->belongsTo(ResortPosition::class, 'target_position_id');
    } 

    public function approvals()
    {
        return $this->hasMany(EmployeeTransferApproval::class, 'transfer_id');
    }

    public function reporting(){
        return $this->belongsTo(Employee::class, 'reporting_manager');
    }

}