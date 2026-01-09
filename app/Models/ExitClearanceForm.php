<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class ExitClearanceForm extends Model
{
    use HasFactory;
    protected $table="exit_clearance_form";
    protected $guarded = ['id'];

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

    public const FORM_TYPES_DEPARTMENT = 'department';
    public const FORM_TYPES_EMPLOYEE = 'employee';

    public const FORM_TYPES = [
        'department' => 'Department',
        'employee' => 'Employee'
    ];
    public function department(){
      return $this->belongsTo(ResortDepartment::class, 'department_id', 'id');  
    }

    public function employee(){
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    
}
