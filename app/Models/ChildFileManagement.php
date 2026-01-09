<?php

namespace App\Models;
use App\Helpers\Common;
use Carbon\Carbon;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildFileManagement extends Model
{
    use HasFactory;
    protected $table = 'child_file_management';
    protected $fillable = ['resort_id','Parent_File_ID','File_Name','File_Type','File_Size','File_Path','File_Extension',
        'File_Upload_By','File_Upload_Date','File_Upload_Time','File_Upload_IP','unique_id','NewFileName','is_secure'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // if (!$model->exists) {
            //     $model->File_Upload_By = Auth::guard('resort-admin')->user()->id;
            // }

            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->File_Upload_By = $user->id;
                }
            }
            
            $todayDate = Carbon::today()->format('Y-m-d'); // Get today's date in YYYY-MM-DD format
   
            if (!$model->exists) {
            
                $model->File_Upload_Date =  Carbon::now()->format('d/m/Y'); 
            }
            if (!$model->exists) {
                $model->File_Upload_Time = Carbon::now()->format('h:i A');
            }
            
            if (!$model->exists) {
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                  $ip=  $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip=  explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // Get first IP in case of multiple proxies
                } else {
                    $ip=  $_SERVER['REMOTE_ADDR'];
                }

            
                $model->File_Upload_IP = $ip;
            }
        });
    }


    
}
