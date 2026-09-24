<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Delivery/read receipt, pushed to the ORIGINAL SENDER's private inbox
 * channel (chat.{sender_id}) — the one channel every client is always
 * subscribed to, so the ticks update live without the sender having the
 * thread open.
 *
 * type_id is the thread key from the SENDER's point of view: the reader's
 * id for an individual chat, the group id for a group chat.
 */
class MessageReceipt implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $senderId;
    public $status;
    public $type;
    public $threadId;
    public $readerId;
    public $messageIds;
    public $at;

    public function __construct($senderId, $status, $type, $threadId, $readerId, array $messageIds, $at)
    {
        $this->senderId = $senderId;
        $this->status = $status;
        $this->type = $type;
        $this->threadId = $threadId;
        $this->readerId = $readerId;
        $this->messageIds = $messageIds;
        $this->at = $at;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->senderId);
    }

    public function broadcastWith()
    {
        return [
            'status' => $this->status,          // 'delivered' | 'read'
            'type' => $this->type,
            'type_id' => $this->threadId,
            'reader_id' => $this->readerId,
            'message_ids' => $this->messageIds,
            'at' => (string) $this->at,
        ];
    }
}
