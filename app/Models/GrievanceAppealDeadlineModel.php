<?php

namespace App\Models;

use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceAppealDeadlineModel extends Model
{
    use HasFactory;
    protected $table="grievance_appeal_deadline_models";

    protected $fillable = [
        'resort_id',
        'AppealDeadLine',
        'date',
        'MemberId_or_CommitteeId',
        'Appeal_Type',
        'Proccess',
        'created_by',
        'modified_by',
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
}
