<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
class ParentSurvey extends Model
{

    use HasFactory;

    public $table='parent_surveys';
    protected $fillable = [
        'resort_id','Surevey_title','Start_date','End_date','Recurring_survey','Reminder_notification','Min_response','Allow_edit','Status','created_by','modified_by','survey_privacy_type' ];
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
