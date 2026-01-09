<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class GrievanceTempleteModel extends Model
{
    use HasFactory;

    protected $table="grievance_templete_models";

    protected $fillable = [
                            'resort_id',
                            'Grievance_Cat_id',
                            'Grievance_Temp_Structure',
                            'Grievance_Temp_name',
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
    
        public function category()
        {
            return $this->belongsTo(GrievanceCategory::class, 'Grievance_Cat_id', 'id');
        }
}
