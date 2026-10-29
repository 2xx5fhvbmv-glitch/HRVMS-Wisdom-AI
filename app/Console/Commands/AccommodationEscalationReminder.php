<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintanaceRequest;
use App\Models\EscalationDay;
use App\Models\AvailableAccommodationModel;
use App\Helpers\Common;
use Carbon\Carbon;

class AccommodationEscalationReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accommodation:escalation-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify staff when a maintenance request passes the configured Escalation Day threshold, or a building hits its critical occupancy threshold — both configs were display-only (colored a table row) with no cron ever consuming them.';

    public function handle()
    {
        $this->notifyOverdueMaintenance();
        $this->notifyCriticalOccupancy();
    }

    private function notifyOverdueMaintenance()
    {
        $defaultEscalationDays = (int) config('settings.EscalationDay', 0);

        MaintanaceRequest::whereNotIn('Status', ['Closed'])
            ->whereNull('escalation_notified_at')
            ->chunk(100, function ($requests) use ($defaultEscalationDays) {
                $thresholdByResort = [];

                foreach ($requests as $request) {
                    if (!array_key_exists($request->resort_id, $thresholdByResort)) {
                        $config = EscalationDay::where('resort_id', $request->resort_id)->first();
                        $thresholdByResort[$request->resort_id] = $config ? (int) $config->EscalationDay : $defaultEscalationDays;
                    }
                    $threshold = $thresholdByResort[$request->resort_id];
                    if ($threshold <= 0) continue;

                    $daysSinceRequest = (int) now()->diffInDays(Carbon::parse($request->date), true);
                    if ($daysSinceRequest <= $threshold) continue;

                    $recipients = array_values(array_unique(array_filter(array_merge(
                        [$request->Assigned_To],
                        Common::getResortHrEmployeeIds($request->resort_id)
                    ))));
                    if (empty($recipients)) continue;

                    try {
                        Common::notifyEmployees(
                            $request->resort_id,
                            $recipients,
                            'Maintenance Request Overdue',
                            "Maintenance request {$request->Request_id} has been open for {$daysSinceRequest} days, past the {$threshold}-day escalation threshold.",
                            'Accommodation',
                            $request->id
                        );
                        $request->update(['escalation_notified_at' => now()]);
                    } catch (\Exception $e) {
                        \Log::warning('Maintenance escalation notification failed for request ' . $request->id . ': ' . $e->getMessage());
                    }
                }
            });
    }

    // ponytail: re-notifies every day a building stays at/below threshold
    // (no dedup state, unlike the maintenance path above) — upgrade to a
    // last-notified-date column if daily repeats prove too noisy.
    /**
     * Safety net for the per-bed check (App\Services\RoomOccupancyAlert): HR is told once when a room reaches its
     * occupancy alert level (percent full). The old version compared FREE beds with the level, so a 90% level fired
     * as soon as 10% of beds were taken, every day, from occupancy_levels_hit_a_critical_thresholds.
     */
    private function notifyCriticalOccupancy()
    {
        \App\Services\RoomOccupancyAlert::checkAll();
    }
}
