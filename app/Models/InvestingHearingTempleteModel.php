<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class InvestingHearingTempleteModel extends Model
{
    use HasFactory;

    protected $table = 'investing_hearing_templete_models';
    protected $fillable = ['resort_id','Hearing_Temp_name','Hearing_Temp_Structure','created_by','modified_by'];


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
}
