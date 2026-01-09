<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class disciplinarySubmit extends Model
{
    use HasFactory;
    public $table="disciplinary_submits";
    public $fillable = [
                            'resort_id',
                            'Disciplinary_id',
                            'Employee_id',
                            'Committee_id',
                            'Category_id',
                            'SubCategory_id',
                            'Offence_id',
                            'Action_id',
                            'Severity_id',
                            'Expiry_date',
                            'Incident_description',
                            'Acknowledgment_description',
                            'witness_id',
                            'Attachements',
                            'upload_signed_document',
                            'Request_For_Statement',
                            'select_witness',
                            'status',
                            'Priority',
                            'Assigned',
                            'created_by',
                            'modified_by',
                            'SendtoHr'
                        ];
    
    public static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if (!$model->exists) 
            {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }
            if(Auth::guard('resort-admin')->check()) 
            {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

 

    public function category()
    {
        return $this->belongsTo(DisciplinaryCategoriesModel::class, 'Category_id', 'id');
    }
    public function offence()
    {
        return $this->belongsTo(OffensesModel::class, 'Offence_id', 'id');
    }

    public function action()
    {
        return $this->belongsTo(ActionStore::class, 'Action_id', 'id');
    }
    public function GetEmployee()
    {
        return $this->belongsTo(Employee::class, 'Employee_id', 'id');
    }

}
