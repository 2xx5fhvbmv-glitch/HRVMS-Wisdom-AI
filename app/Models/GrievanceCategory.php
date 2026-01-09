<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class GrievanceCategory extends Model
{
    use HasFactory;

    protected $table="grievance_categories";

    protected $fillable = [
        'resort_id',
        'Category_Name',
        'Category_Description',
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

    public function subcategories()
    {
        return $this->hasMany(GrievanceSubcategory::class, 'Grievance_Cat_id', 'id');
    }


    

    public function GrievancesSubmisstion()
    {
        return $this->hasMany(GrivanceSubmissionModel::class, 'Grivance_Cat_id', 'id');
    }
   
}
