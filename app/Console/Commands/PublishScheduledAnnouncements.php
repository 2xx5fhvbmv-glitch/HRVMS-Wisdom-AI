<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Announcement;
use Carbon\Carbon;


class PublishScheduledAnnouncements extends Command
{
    protected $signature = 'announcements:publish-scheduled';
    protected $description = 'Automatically publish scheduled announcements when their date is due';

    public function handle()
    {
        $now = Carbon::now()->startOfDay();

        $announcements = Announcement::where('status', 'Scheduled')
            ->whereDate('published_date', '<=', $now)
            ->where('archived', false)
            ->get();

        foreach ($announcements as $announcement) {
            $announcement->status = 'Published';
            $announcement->save();

            $this->info("Published: Announcement ID {$announcement->id}");
        }

        return Command::SUCCESS;
    }
}
