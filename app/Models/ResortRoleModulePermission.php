<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResortRoleModulePermission extends Model
{
    protected $table = "resort_roles_modules_permissions";

    protected $fillable = [
        'resort_id','role_id','module_permission_id'
    ];

    public function module_permission()
    {
        return $this->belongsTo(ResortModulePermission::class,'module_permission_id');
    }

    public function resort()
    {
        return $this->belongsTo(Resort::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'dept_id');
    }

}

?>
