<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\GroupChatMember;

class GroupChat extends Model
{
    use HasFactory,Notifiable;
    use SoftDeletes;

    protected $table = "chat_group";
    protected $guarded = ['id'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if(Auth::guard('resort-admin')->check()) {
                if (!$model->exists) {
                    $model->created_by = Auth::guard('resort-admin')->user()->id;
                }

                if(Auth::guard('resort-admin')->check()) {
                    $model->modified_by = Auth::guard('resort-admin')->user()->id;
                }
            }
        });
    }

    
   public function groupMembers()
    {
        return $this->hasMany(GroupChatMember::class, 'chat_group_id', 'id');
    }

    
}
