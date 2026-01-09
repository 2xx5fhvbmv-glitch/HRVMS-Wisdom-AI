<?php
namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel; 
use Illuminate\Broadcasting\PrivateChannel;  
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Conversation $message)
    {
        $this->message = $message;
    }

    // For individual (private) conversation
    public function broadcastOn()
    {
        if ($this->message->type === 'group') {
            // Group chat on Presence or Private channel
            \Log::info('Broadcast group event triggered with message id: ' . $this->message->id);

            return new PresenceChannel('group.' . $this->message->type_id);
        } else {
            \Log::info('Broadcast individual event triggered with message id: ' . $this->message->id);

            // One-to-one chat: use a private channel
            return new PrivateChannel('chat.' . $this->message->type_id);
        }
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'type_id' => $this->message->type_id,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}
