<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilePermissions extends Model
{
    use HasFactory;
    protected $table = 'file_permissions';
    
    protected $fillable = [
        'resort_id',
        'Department_id',
        'Position_id',
        'file_id',
    ];
}
