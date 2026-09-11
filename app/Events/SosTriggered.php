<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Fired from SOSStore() (new Pending SOS) and handleSOSActionWithTeam()
 * (approved/rejected/dispatched) — the web dashboard had no way to know a
 * new SOS came in except manually refreshing the page. ShouldBroadcastNow,
 * not queued: a panic-button alert needs to reach the dashboard
 * immediately, not whenever the queue worker next runs.
 */
class SosTriggered implements ShouldBroadcastNow
{
    use SerializesModels;

    public $resortId;
    public $sosId;
    public $status;
    public $emergencyName;
    public $location;
    public $initiatorName;
    public $initiatorPhoto;

    public function __construct($resortId, $sosId, $status, $emergencyName, $location, $initiatorName, $initiatorPhoto = null)
    {
        $this->resortId = $resortId;
        $this->sosId = $sosId;
        $this->status = $status;
        $this->emergencyName = $emergencyName;
        $this->location = $location;
        $this->initiatorName = $initiatorName;
        $this->initiatorPhoto = $initiatorPhoto;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('resort.' . $this->resortId . '.sos');
    }

    public function broadcastAs()
    {
        return 'SosTriggered';
    }

    public function broadcastWith()
    {
        return [
            'sos_id' => $this->sosId,
            'status' => $this->status,
            'emergency_name' => $this->emergencyName,
            'location' => $this->location,
            'initiator_name' => $this->initiatorName,
            'initiator_photo' => $this->initiatorPhoto,
        ];
    }
}
