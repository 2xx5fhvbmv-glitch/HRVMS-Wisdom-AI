<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConversationMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $sender_id;
    public $receiver_id;
    public $created_at;

    public function __construct($message, $sender_id, $receiver_id, $created_at)
    {
        $this->message = $message;
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->created_at = $created_at;
    }

    public function broadcastOn()
    {
        // Use a private channel for user-to-user chats
        \Log::info('Broadcasting message to: conversation.' . $this->receiver_id);
        return new PrivateChannel('conversation.' . $this->receiver_id);
    }

    public function broadcastWith()
    {
        return [
            'message'     => $this->message,
            'sender_id'   => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'created_at'  => $this->created_at,
        ];
    }
}
