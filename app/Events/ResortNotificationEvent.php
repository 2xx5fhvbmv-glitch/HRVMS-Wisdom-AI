<?php

namespace App\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResortNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $html;
    public function __construct($html)
    {
        $this->html= $html;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return self::channelsFor($this->html['resortid'] ?? '');
    }

    /**
     * One private channel per resort named in the payload (admin fan-out sends a CSV of ids). routes/channels.php
     * admits only that resort's own users; the old shared public channel let anyone with the Pusher key read every
     * resort's notifications.
     */
    public static function channelsFor($resortIds): array
    {
        return collect(explode(',', (string) $resortIds))->map(fn ($id) => (int) trim($id))->filter()->unique()
            ->map(fn ($id) => new \Illuminate\Broadcasting\PrivateChannel('resort-notifications.' . $id))->values()->all();
    }
    public function broadcastAs()
    {
        return 'ResorteNotification-event';
    }
}
