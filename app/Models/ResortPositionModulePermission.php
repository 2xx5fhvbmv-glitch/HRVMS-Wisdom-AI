<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResortPositionModulePermission extends Model
{
    protected $table = "resort_position_module_permissions";

    protected $fillable = [
        'position_id','module_permission_id'
    ];

    /**
     * Get the module permission associated with the position.
     */
    public function module_permission()
    {
        return $this->belongsTo(ResortModulePermission::class, 'module_permission_id');
    }
}

?>