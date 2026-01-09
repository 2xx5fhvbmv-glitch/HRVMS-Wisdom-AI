<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Events;
use App\Models\ChildEvents;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;

class CalenderPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:calendar-push-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notification for calendar events';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now    =   Carbon::now();
        $emp    =   Events::where('status', 'accept')
                            ->where('reminder_days', 'LIKE', '%-H%') // Filter only for reminder_days containing -H (1-H, 2-H, 3-H, etc.)
                            // ->where('date', '>=', Carbon::today()->toDateString())
                            ->where('date', '=', Carbon::today()->toDateString())
                            // ->where('time', '>=', Carbon::now())
                            ->get()
                             ->map(function ($event) use ($now) {
                            // Combine date and time to a Carbon instance
                            $eventDateTime          =   Carbon::parse($event->date . ' ' . $event->time);

                            // Extract hours from reminder_days (e.g., 1 from 1-H)
                            if (preg_match('/^(\d+)-H$/', $event->reminder_days, $matches)) {
                            // if (preg_match('/^([123])-H$/', $event->reminder_days, $matches)) {
                                // $reminderHours      =   (int)$matches[1];
                                // $diffInHours        =   $now->diffInHours($eventDateTime, false);

                                $reminderHours = (int)$matches[1];
                                $reminderMinutes = $reminderHours * 60;
                                $diffInMinutes = $now->diffInMinutes($eventDateTime, false);
        
                                // dd($reminderHours);
                                // Only process if the event is exactly X hours away
                                // if ($diffInHours === $reminderHours) {
                                if ($diffInMinutes === $reminderMinutes) {
                                    $event->child_event     =   ChildEvents::join('employees', 'employees.id', '=', 'child_events.employee_id')
                                                                ->where('child_events.event_id', $event->id)
                                                                ->select('child_events.*', 'employees.device_token')
                                                                ->get()
                                                                ->map(function ($childEvent) {

                                            // Prepare the notification details
                                            $data['title']      = "Upcoming Event";
                                            $data['body']       = "You have an upcoming event.";
                                            $data['moduleName'] = "Calendar";
                                            // Uncomment to send notification
                                            // Common::sendPushNotifictionForMobile($childEvent->device_token, $title, $body, $moduleName,NULL,NULL);
                                            return $data;
                                        });
                                    return $event;
                                }
                            }
                            // If not matching, return null (will be filtered out)
                            return null;
                        })
                        ->filter()
                        ->values();

                            echo "<pre>";
                            print_r($emp->toArray());
                            echo "</pre>";
                            exit();
    }   
}