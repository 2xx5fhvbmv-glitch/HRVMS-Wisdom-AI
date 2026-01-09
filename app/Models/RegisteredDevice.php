<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisteredDevice extends Model
{
    use HasFactory;
    protected $table= 'registered_devices';
    protected $fillable=['emp_id','device_id'];
}
