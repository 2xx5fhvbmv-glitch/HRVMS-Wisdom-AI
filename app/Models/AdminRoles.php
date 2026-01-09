<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminRoles extends Authenticatable
{
  protected $guard = 'admin_roles';

  protected $fillable = [
    'guard_name', 'name', 'status'
  ];

  protected $dates = ['created_at','updated_at'];
}
