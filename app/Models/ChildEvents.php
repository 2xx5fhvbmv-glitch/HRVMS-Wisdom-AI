<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildEvents extends Model
{
    use HasFactory;

    protected $table = 'child_events';
    protected $fillable = ['resort_id','event_id','employee_id'];

}
