<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\DisableAdvertismentExpiredLinks;
use App\Console\Commands\SurveychangeStatus;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('weekly:plan_rollover')->weeklyOn(1, '00:00');
        // $schedule->command('links:JobAdvertisment-disable-expired')->everyMinute();
        $schedule->command('learning:update-status')->dailyAt("00:00");
        $schedule->command('links:JobAdvertisment-disable-expired')->dailyAt("00:00");
        $schedule->command('links:survey-change-status')->dailyAt("00:00");
        $schedule->command('incident:send-meeting-reminders')->dailyAt('09:00');
        $schedule->command('announcements:publish-scheduled')->daily(); // or hourly()
        $schedule->command('monthly-check-in:update-status')->hourly();
        $schedule->command('links:onboarding-new-emp-hire-notification')->dailyAt('09:00');
        $schedule->command('weekly:CheckExtraHours')->weeklyOn(1, '00:00');
        $schedule->command('Daily:CheckExtraHours')->daily();
        $schedule->command('Monthly:CheckEveryVisaModule')->monthly();
        $schedule->command('CheckHourly:IncidentCompliance')->everyMinute();

        

    }
    


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');


    }
}
