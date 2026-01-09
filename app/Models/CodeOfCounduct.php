<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class CodeOfCounduct extends Model
{
    use HasFactory;
    public $table = 'code_of_counducts';
    public $fillable=[ 'resort_id','Action_id','Deciplinery_cat_id','Offenses_id','Severity_id','created_by','modified_by'];
        

        // public static function boot(){
        //     parent::boot();
    
        //     self::saving(function ($model) {
        //         if (!$model->exists) {
        //             $model->created_by = Auth::guard('resort-admin')->user()->id;
        //         }
    
        //         if(Auth::guard('resort-admin')->check()) {
        //             $model->modified_by = Auth::guard('resort-admin')->user()->id;
        //         }
        //     });
        // }
}
