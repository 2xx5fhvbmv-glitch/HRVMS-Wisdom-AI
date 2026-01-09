<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class SOSRolesAndPermission extends Model
{
    use HasFactory;

    protected $table="sos_role_management";

    protected $fillable = [
        'resort_id',
        'name',
        'permission',
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

    
    public function sosTeamMember()
    {
        return $this->hasMany(SOSTeamMemeberModel::class, 'role_id', 'id');
    }

    
}
