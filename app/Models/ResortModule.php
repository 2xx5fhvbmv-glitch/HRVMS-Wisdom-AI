<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResortModule extends Model
{
    protected $table = "resort_modules";

    protected $fillable = [
        'name'
    ];

    public function module_permissions()
    {
        return $this->hasMany(ResortModulePermission::class,'module_id');
    }
   
}

?>