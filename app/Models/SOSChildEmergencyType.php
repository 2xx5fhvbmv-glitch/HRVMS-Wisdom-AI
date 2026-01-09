<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class SOSChildEmergencyType extends Model
{
    use HasFactory;

    protected $table = 'sos_child_emergency_types';

    protected $fillable = [
        'emergency_id',
        'team_id',
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

    // Relationships (optional)
    public function emergencyType()
    {
        return $this->belongsTo(SOSEmergencyTypesModel::class, 'emergency_id');
    }

    public function team()
    {
        return $this->belongsTo(SOSTeamManagementModel::class, 'team_id');
    }
}
