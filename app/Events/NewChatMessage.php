<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
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
    public $supportId;

    public function __construct($message, $senderId, $receiverId, $senderName, $senderImage, $receiverName, $receiverImage, $attachments = [], $supportId = null)
    {
        $this->message = $message;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderName = $senderName;
        $this->senderImage = $senderImage;
        $this->receiverName = $receiverName;
        $this->receiverImage = $receiverImage;
        $this->attachments = is_array($attachments) ? $attachments : [];
        $this->supportId = $supportId;
    }

    public function broadcastOn()
    {
        // Private, per-ticket channel. Both admin and resort-admin guards
        // are authorized in routes/channels.php; /broadcasting/auth
        // (routes/web.php) resolves both guards before calling Broadcast::auth().
        return new PrivateChannel('support-ticket.' . $this->supportId);
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


