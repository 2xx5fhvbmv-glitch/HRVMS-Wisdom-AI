<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminPasswordReset extends Model
{
	public $timestamps = false;
	protected $table = 'admins_password_resets';
}

?>