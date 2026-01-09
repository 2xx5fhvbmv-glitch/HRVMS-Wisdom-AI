<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class SosTeamMemberActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sos_team_member_activity';

    protected $fillable = [
        'sos_history_id',
        'team_id',
        'emp_id',
        'address',
        'latitude',
        'longitude',
        'status',
        'created_by',
        'modified_by',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
           $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(SOSTeamManagementModel::class);
    }

    public function resortAdmin()
    {
        return $this->belongsTo(ResortAdmin::class, 'emp_id');
    }

    public function sos()
    {
        return $this->belongsTo(SOSHistoryModel::class, 'sos_history_id');
    }

    public function memberRole()
    {
        return $this->hasOneThrough(
            \App\Models\SOSRolesAndPermission::class, // Final model
            \App\Models\SOSTeamMemeberModel::class,    // Intermediate model
            'emp_id',     // Foreign key on SOSTeamMemeberModel
            'id',         // Foreign key on SOSRolesAndPermission
            'emp_id',     // Local key on SosTeamMemberActivity
            'role_id'     // Local key on SOSTeamMemeberModel
        );
    }

}
