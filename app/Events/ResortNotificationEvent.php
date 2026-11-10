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
        // Audit run-3 M1: a notification for one person (`sendto` = their employees.id) goes on that person's own
        // channel — on the resort-wide one every user of the resort received it and only page JS hid it.
        $sendto = (int) ($this->html['sendto'] ?? 0);
        if ($sendto > 0) {
            return collect(explode(',', (string) ($this->html['resortid'] ?? '')))->map(fn ($id) => (int) trim($id))->filter()->unique()
                ->map(fn ($id) => new PrivateChannel("resort-notifications.{$id}.employee.{$sendto}"))->values()->all();
        }
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
