<?php

namespace App\Jobs;

use App\Services\Wisdom\PolicyLibrary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Re-index one resort's "Company Policies" folder for Wisdom AI after a file there was added, moved or deleted.
 * Indexing a large PDF can outlast the database queue's retry_after (90s), so a per-resort lock keeps a second
 * copy from running alongside; a copy that finds the lock taken comes back a minute later instead of being lost.
 */
class SyncCompanyPolicies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 900;
    public $tries = 0;

    public function __construct(public int $resortId)
    {
    }

    public function handle(): void
    {
        $lock = Cache::lock("company-policies:{$this->resortId}", 900);
        if (!$lock->get()) {
            $this->release(60);
            return;
        }
        try {
            PolicyLibrary::sync($this->resortId);
        } finally {
            $lock->release();
        }
    }
}
