<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class SOSTeamMemeberModel extends Model
{
    use HasFactory;

    protected $table="sos_team_members";

    protected $fillable = [
        'resort_id',
        'team_id',
        'emp_id',
        'role_id',
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

    public function employee(){
        return $this->belongsTo(Employee::class, 'emp_id', 'id');
    }

    public function team(){
        return $this->belongsTo(SOSTeamManagementModel::class, 'team_id', 'id');
    }
    public function teamMember(){
        return $this->belongsTo(ResortAdmin::class, 'emp_id', 'id');
    }
    public function resortAdmin(){
        return $this->belongsTo(ResortAdmin::class, 'emp_id', 'id');
    }
    public function memberRole(){
        return $this->belongsTo(SOSRolesAndPermission::class, 'role_id', 'id');
    }

    public function resortAdminId()
    {
        return $this->belongsTo(ResortAdmin::class, 'id'); // Adjust column if needed
    }

}
