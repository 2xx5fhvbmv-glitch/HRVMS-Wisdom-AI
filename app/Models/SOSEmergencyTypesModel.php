<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class SOSEmergencyTypesModel extends Model
{
    use HasFactory;
    protected $table="sos_emergency_types";

    protected $fillable = [
        'resort_id',
        'name',
        'description',
        'custom_fields',
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

    public function sosType(){
        return $this->hasMany(SOSHistoryModel::class);
    }

    public function assignedTeams()
    {
        return $this->hasMany(SOSChildEmergencyType::class, 'emergency_id');
    }
    
}
