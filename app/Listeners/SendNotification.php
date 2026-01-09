<?php
namespace App\Listeners;

use App\Events\NotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ResortNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendNotification
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(NotificationEvent $event)
    {
        foreach ($event->userIds as $userId) {
            // Store the notification in the database
            $notification = ResortNotification::create([
                'resort_id' => $event->resort_id,
                'user_id' => $userId,
                'module' => $event->module,
                'type' => $event->type,
                'message' => $event->message,
                'status' => 'unread',
            ]);

            // Log for debugging
            Log::info("Notification stored for User ID: {$userId}", ['notification_id' => $notification->id]);

            // Send the WebSocket notification
            try {
                $base_url = env('BASE_URL', 'http://localhost:3000');
                $response = Http::post($base_url . '/send-notification', [
                    'user_id' => $userId,
                    'notification_id' => $notification->id,
                    'message' => $event->message,
                    'module' => $event->module,
                    'type' => $event->type,
                    'resort_id' => $event->resort_id,
                ]);

                // $response = Http::post('https://projects.spaculus.live:3000/send-notification', [
                //     'user_id' => $userId,
                //     'notification_id' => $notification->id,
                //     'message' => $event->message,
                //     'module' => $event->module,
                //     'type' => $event->type,
                //     'resort_id' => $event->resort_id,
                // ]);

                Log::info("WebSocket response for User ID: {$userId}", ['response' => $response->body()]);
            } catch (\Exception $e) {
                Log::error("WebSocket error for User ID: {$userId}", ['error' => $e->getMessage()]);
            }
        }
    }
}
