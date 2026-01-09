<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class NewChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $senderId;
    public $receiverId;
    public $senderName;
    public $senderImage;
    public $receiverName;
    public $receiverImage;

    public function __construct($message, $senderId, $receiverId, $senderName, $senderImage, $receiverName, $receiverImage)
    {
        $this->message = $message;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderName = $senderName;
        $this->senderImage = $senderImage;
        $this->receiverName = $receiverName;
        $this->receiverImage = $receiverImage;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->receiverId);
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'senderId' => $this->senderId,
            'receiverId' => $this->receiverId,
            'senderName' => $this->senderName,
            'senderImage' => $this->senderImage,
            'receiverName' => $this->receiverName,
            'receiverImage' => $this->receiverImage,
        ];
    }
}


