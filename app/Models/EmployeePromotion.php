<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;


class EmployeePromotion extends Model
{
    use HasFactory;
    protected $table = 'employee_promotions';

    protected $fillable = [
        'resort_id','employee_id','current_position_id','new_position_id','effective_date',
        'new_level','current_salary','salary_increment_percent','salary_increment_amount','Jd_id',
        'new_salary','updated_benefit_grid','comments','status','follow_up_date','letter_dispatched','created_by','modified_by'
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
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    
    public function currentPosition() {
        return $this->belongsTo(ResortPosition::class, 'current_position_id');
    }
    
    public function newPosition() {
        return $this->belongsTo(ResortPosition::class, 'new_position_id');
    } 

    public function approvals()
    {
        return $this->hasMany(EmployeePromotionApproval::class, 'promotion_id');
    }
    public function approval()
    {
        return $this->belongsTo(EmployeePromotionApproval::class, 'promotion_id','id')->where('status', 'Pending')->where('approver_id', Auth::guard('resort-admin')->user()->id);
    }

    public function createdBy()
    {
        return $this->belongsTo(ResortAdmin::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(ResortAdmin::class, 'modified_by');
    }

}