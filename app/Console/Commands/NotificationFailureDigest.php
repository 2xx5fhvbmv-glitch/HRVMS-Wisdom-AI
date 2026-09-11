<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily digest of push-notification failures already being written to
 * storage/logs/laravel.log by Common.php (FCM credential/auth/send errors)
 * — those lines currently just sit in a file nobody tails. This counts
 * yesterday's occurrences per known failure pattern and writes one
 * greppable summary line, so "notifications silently stopped working" is
 * visible without reading the raw log. See
 * docs/notification-guardrails.md #2 — start here, upgrade to a
 * notification_failures table only if this shows the volume is worth it.
 */
class NotificationFailureDigest extends Command
{
    protected $signature = 'notifications:failure-digest';
    protected $description = 'Log a daily count of push-notification failures from the last 24h.';

    private const PATTERNS = [
        'fcm_credentials_missing' => 'FCM credentials missing',
        'fcm_jwt_signing_failed'  => 'FCM JWT signing failed',
        'fcm_oauth_token_failed'  => 'FCM OAuth token request failed',
        'fcm_curl_error'          => 'FCM cURL Error',
        'fcm_send_error'          => 'FCM Error:',
        'invalid_sendto_id'       => 'sendMobileNotification: $sendto contains',
        'invalid_empid'           => 'notifyEmployees: $empIds contains',
    ];

    public function handle()
    {
        $path = storage_path('logs/laravel.log');
        if (!is_file($path)) {
            $this->info('No log file found.');
            return 0;
        }

        $since = Carbon::now()->subDay();
        $counts = array_fill_keys(array_keys(self::PATTERNS), 0);
        $total = 0;

        $handle = fopen($path, 'r');
        while (($line = fgets($handle)) !== false) {
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) {
                continue;
            }
            if (Carbon::parse($m[1])->lt($since)) {
                continue;
            }
            foreach (self::PATTERNS as $key => $needle) {
                if (str_contains($line, $needle)) {
                    $counts[$key]++;
                    $total++;
                }
            }
        }
        fclose($handle);

        if ($total === 0) {
            $this->info('No notification failures in the last 24h.');
            return 0;
        }

        $breakdown = collect($counts)->filter()->map(fn ($c, $k) => "{$k}={$c}")->implode(', ');
        \Log::warning("NOTIFICATION_FAILURE_DIGEST: {$total} failure(s) in the last 24h ({$breakdown})");
        $this->warn("{$total} notification failure(s) in the last 24h: {$breakdown}");

        return 0;
    }
}
