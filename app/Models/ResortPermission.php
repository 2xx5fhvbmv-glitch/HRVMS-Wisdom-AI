<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResortPermission extends Model
{
    protected $table = 'resort_permissions';

    protected $fillable = [
        'name', 'order'
    ];
}
