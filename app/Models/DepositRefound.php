<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositRefound extends Model
{
    use HasFactory;
    protected $table = 'deposit_refounds';
    protected $fillable = ['resort_id','initial_reminder','followup_reminder','short_name','status'];
}
