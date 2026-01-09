<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResortModulePermission extends Model
{
    protected $table = "resort_module_permissions";
    
    protected $fillable = [
        'module_id','permission_id'
    ];

    /**
     * Get the permission associated with the module permission.
     */
    public function permission()
    {
        return $this->belongsTo(ResortPermission::class, 'permission_id');
    }

    /**
     * Get the module associated with the module permission.
     */
    public function module()
    {
        return $this->belongsTo(ResortModule::class, 'module_id');
    }
}

?>