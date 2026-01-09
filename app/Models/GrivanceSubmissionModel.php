<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class GrivanceSubmissionModel extends Model
{
    use HasFactory;
    protected $table="grivance_submission_models";

    protected $fillable = [
                            'Gm_Resoan',
                            'resort_id',
                            'Grivance_id',
                            'Grivance_Cat_id',
                            'Grivance_Sub_cat',
                            'Employee_id',
                            'status',
                            'date',
                            'Grivance_description',
                            'Grivance_date_time',
                            'location',
                            'witness_id',
                            'Grivance_Eexplination_description',
                            'Grivance_Submission_Type',
                            'grievance_informally',
                            'Attachements',
                            'Priority',
                            'Assigned',
                            'Committee_id',
                            'created_by',
                            'modified_by',
                            'outcome_type',
                            'action_taken',
                            'Request_Identity_Disclosure',
                            'Gm_Decision',
                            'Rejection_reason',
                            'RequestforStatment'
                        ];
                
        public static function boot(){
            parent::boot();
    
            self::saving(function ($model) {
                
                $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

                if ($user) {
                    if (!$model->exists) {
                        $model->created_by = $user->id;
                        $model->modified_by = $user->id;
                    }
                }

            });
        }
        public function category()
        {
            return $this->belongsTo(GrievanceCategory::class, 'Grivance_Cat_id', 'id');
        }
        public function GetEmployee()
        {
            return $this->belongsTo(Employee::class, 'Employee_id', 'id');
        }


         public function CategoryWiseGrivane()
        {
            return $this->belongsTo(GrievanceCategory::class, 'Grivance_Cat_id', 'id');
        }
       
}
