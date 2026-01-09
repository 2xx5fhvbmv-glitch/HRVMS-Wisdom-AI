<?php

namespace App\Models;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineryAssignCommittee extends Model
{
    use HasFactory;

    
    public $table='disciplinery_assign_committees';
    
    public $fillable=['resort_id','CommitteeName','date','created_by','modified_by'];
 
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
