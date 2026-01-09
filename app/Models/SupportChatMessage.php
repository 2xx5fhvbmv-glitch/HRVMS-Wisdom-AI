<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportChatMessage extends Model
{
    use HasFactory;
    protected $table = 'support_chat_messages';


    protected $fillable = [
        'support_id',
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'message',
        'attachment',
        'message_id',
        'in_reply_to',
        'subject',
        'is_email',
    ];

    public function support() {
        return $this->belongsTo(Support::class);
    }

    public function sender() {
        return $this->morphTo(null, 'sender_type', 'sender_id');
    }

    public function receiver() {
        return $this->morphTo(null, 'receiver_type', 'receiver_id');
    }
}
