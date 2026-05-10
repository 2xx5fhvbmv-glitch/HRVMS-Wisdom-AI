<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Session-scoped dismissal of the admin broadcast banner that renders at
 * the top of the resort layout. We push the notification id into the
 * current session — no DB write — so the banner returns on the next
 * fresh login (session is rebuilt at login). The banner stops appearing
 * for everyone once notifications.end_date passes, regardless of any
 * dismissals.
 */
class AdminBroadcastNotificationController extends Controller
{
    public function dismiss(int $id)
    {
        $user = Auth::guard('resort-admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }
        $dismissed = (array) session('dismissed_admin_notifications', []);
        $dismissed[] = (int) $id;
        session(['dismissed_admin_notifications' => array_values(array_unique($dismissed))]);

        return response()->json(['success' => true]);
    }
}
