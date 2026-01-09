<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class GrivanceResoultionTimeLineModel extends Model
{
    use HasFactory;

    protected $table="grivance_resoultion_time_line_models";

    protected $fillable = [
                            'resort_id',
                            'HighPriority',
                            'MediumPriority',
                            'LowPriority',
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
