<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Departmen;
use App\Models\Position;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class GroupChatMember extends Model
{
    use HasFactory,Notifiable;
    use SoftDeletes;
    
    protected $table="chat_group_member";
    protected $guarded = ['id'];

    

}
