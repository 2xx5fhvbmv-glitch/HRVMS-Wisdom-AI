<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeChat extends Model
{
    use HasFactory;

    protected $table='employee_chat_messages';
    protected $fillable=['resort_id','sender_id','receiver_id','conversation_id','message'];
}
