<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userIds;
    public $module;
    public $type;
    public $message;
    public $resort_id;

    public function __construct(array $userIds, string $module, string $type, string $message, int $resort_id)
    {
        $this->userIds = $userIds;
        $this->module = $module;
        $this->type = $type;
        $this->message = $message;
        $this->resort_id = $resort_id;
    }

    public function broadcastOn()
    {
        return new Channel('Resortevent-channel');
    }

    public function broadcastAs()
    {
        return 'NotificationEvent';
    }

    public function broadcastWith()
    {
        return [
            'user_ids' => $this->userIds,
            'module' => $this->module,
            'type' => $this->type,
            'message' => $this->message,
            'resort_id' => $this->resort_id,
        ];
    }
}
