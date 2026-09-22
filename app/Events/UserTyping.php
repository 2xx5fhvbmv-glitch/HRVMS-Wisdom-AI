<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Individual (1-1) typing indicator only. 'chat.{receiver_id}' is each
 * user's own private inbox channel (auth: id === receiver_id), not a
 * shared per-conversation channel, so the sender can't join the
 * recipient's channel to whisper on it directly the way group typing does
 * on the group's presence channel — this event is the server round-trip
 * that stands in for that whisper.
 */
class UserTyping implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $typeId;
    public $senderId;
    public $senderName;

    public function __construct($typeId, $senderId, $senderName)
    {
        $this->typeId = $typeId;
        $this->senderId = $senderId;
        $this->senderName = $senderName;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->typeId);
    }

    public function broadcastWith()
    {
        return [
            'type' => 'individual',
            'type_id' => $this->typeId,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
        ];
    }
}
