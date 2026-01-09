<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminModulePermission extends Model
{
    protected $fillable = [
        'module_id','permission_id'
    ];
}

?>