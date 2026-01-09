<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;


class EmployeeInfoUpdateRequest extends Model
{
    use HasFactory;
    protected $table ='employee_info_update_request';
    protected $guarded = ['id'];

    protected $casts = [
        'info_payload' => 'array',
    ];


    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
            }
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->modified_by = $user->id;
                }
            }

            
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'Dept_id', 'id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'Position_id', 'id');
    }

}
