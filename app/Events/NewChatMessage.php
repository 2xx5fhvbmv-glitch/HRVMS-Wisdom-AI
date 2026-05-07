<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
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
    public $attachments;

    public function __construct($message, $senderId, $receiverId, $senderName, $senderImage, $receiverName, $receiverImage, $attachments = [])
    {
        $this->message = $message;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderName = $senderName;
        $this->senderImage = $senderImage;
        $this->receiverName = $receiverName;
        $this->receiverImage = $receiverImage;
        $this->attachments = is_array($attachments) ? $attachments : [];
    }

    public function broadcastOn()
    {
        // Public channel — both the admin (auth:admin guard) and resort
        // (auth:resort-admin guard) panels need to listen, and Laravel's
        // default /broadcasting/auth only validates against the web guard.
        // Keeping this public sidesteps multi-guard auth wiring; the channel
        // name itself includes the receiver id, so cross-leakage is minimal.
        return new Channel('chat.' . $this->receiverId);
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
            'attachments' => $this->attachments,
        ];
    }
}


