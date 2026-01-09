<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildMaintananceRequest extends Model
{
    use HasFactory;

    protected $table = 'child_maintanance_requests';
    public $fillable = ['resort_id','maintanance_request_id','ApprovedBy','Status','comments','date','rank'];

}
