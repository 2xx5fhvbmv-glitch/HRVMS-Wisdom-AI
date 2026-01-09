<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class EmployeeResignationWithdrawalConfig extends Model
{
    use HasFactory;
    protected $table ='employee_resignation_withdrawal_configuration';
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
    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id', 'id');
    }
}
