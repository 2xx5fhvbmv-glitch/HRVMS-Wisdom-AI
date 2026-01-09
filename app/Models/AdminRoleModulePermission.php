<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminRoleModulePermission extends Model
{
    protected $fillable = [
        'role_id','module_permission_id'
    ];

    public function module_permission()
    {
        return $this->belongsTo(AdminModulePermission::class,'module_permission_id');
    }
}

?>
