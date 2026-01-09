<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class AuditLogs extends Model
{
    use HasFactory;

    public $table='audit_logs';
    public $fillable = ['resort_id','file_id','TypeofAction','file_path','created_by','modified_by'];


    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // if (!$model->exists) {
            //     $model->created_by = Auth::guard('resort-admin')->user()->id;
            // }

            // if(Auth::guard('resort-admin')->check()) {
            //     $model->modified_by = Auth::guard('resort-admin')->user()->id;
            // }

            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }

        });
    }

}
