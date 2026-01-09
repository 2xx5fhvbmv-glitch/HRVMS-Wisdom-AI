<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class SOSTeamManagementModel extends Model
{
    use HasFactory;

    protected $table="sos_teams";

    protected $fillable = [
        'resort_id',
        'name',
        'description',
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

    public function members()
    {
        return $this->hasMany(SOSTeamMemeberModel::class, 'team_id');
    }

    public function emergencyTeam()
    {
        return $this->hasMany(SOSEmergencyTypesModel::class, 'team_id');
    }
    public function team()
    {
        return $this->belongsTo(SOSTeamManagementModel::class, 'team_id');
    }

}
