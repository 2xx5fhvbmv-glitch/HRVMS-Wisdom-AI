<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildApprovedMaintanaceRequests extends Model
{
    use HasFactory;

    protected $table = 'child_approved_maintanace_requests';
    public $fillable = ['resort_id','child_maintanance_request_id','maintanance_request_id','ApprovedBy','Status','date','rank'];
}
