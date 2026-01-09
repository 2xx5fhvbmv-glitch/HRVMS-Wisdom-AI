<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class GrievanceRightToBeAccompanied extends Model
{
    use HasFactory;
    protected $table="grievance_right_to_be_accompanieds";

    protected $fillable = [
                            'resort_id',
                            'Right_to_be_accompanied',
                            'created_by',
                            'modified_by'
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
