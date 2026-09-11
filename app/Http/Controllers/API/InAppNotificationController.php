<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\ResortNotification;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\Employee;
use App\Helpers\Common;

class InAppNotificationController extends Controller
{
    protected $user;
    protected $resort_id;

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user                                 = Auth::guard('api')->user();
            $this->resort_id                            = $this->user->resort_id;
        }
    }

  public function employeeInAppNotification()
  {
    return $this->buildNotificationList(['unread', 'read']);
  }

  /**
   * GET notification/active-list — unread only. Backs the "active
   * notification panel" — a notification tapped/marked read via
   * mark-read (or the older delete-message-read) drops out of this list
   * immediately without needing to reload the whole notification set.
   */
  public function activeNotifications()
  {
    return $this->buildNotificationList(['unread']);
  }

  /**
   * GET notification/inactive-list — read/handled history. The
   * retained audit trail the ticket asked for: nothing is hard-deleted
   * when a notification is read, it just moves out of the active list
   * and into this one.
   */
  public function inactiveNotifications()
  {
    return $this->buildNotificationList(['read']);
  }

  private function buildNotificationList(array $statuses)
  {
    if (!Auth::guard('api')->check()) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    try {
        $notifications                                  = ResortNotification::where('user_id', $this->user->GetEmployee->id)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->whereIn('status', $statuses)
                                                            ->orderBy('created_at', 'desc')
                                                            ->get()->map(function ($notification) {
                                                                if($notification->module == 'Birthday') {
                                                                  $user = Employee::find($notification->request_id);
                                                                  if($notification->request_id != null){
                                                                    $notification->profile_picture =   Common::getResortUserPicture($user->Admin_Parent_id);
                                                                  }
                                                                }
                                                                return [
                                                                  'id'              => $notification->id,
                                                                  'resort_id'       => $notification->resort_id,
                                                                  'user_id'         => $notification->user_id ?? null,
                                                                  'message'         => $notification->message,
                                                                  'status'          => $notification->status,
                                                                  'created_at'      => $notification->created_at,
                                                                  'updated_at'      => $notification->updated_at,
                                                                  'module'          => $notification->module ?? null,
                                                                  'page_id'         => $notification->page_id ?? null,
                                                                  'type'            => $notification->type ?? null,
                                                                  'request_id'      => $notification->request_id ?? null,
                                                                  // getCreatedAtAttribute() already formats this into the
                                                                  // resort's display format (e.g. "29/06/2026 23:24") —
                                                                  // re-parsing that with Carbon::parse() below crashed the
                                                                  // whole endpoint for resorts using a d/m/Y-style setting.
                                                                  '_sort_key'       => $notification->getRawOriginal('created_at'),
                                                                ];
                                                              });

        $Announcement                                   = Announcement::join('announcement_notification as an','an.announcement_id','=','announcement.id')
                                                            ->where('announcement.employee_id',$this->user->GetEmployee->id)
                                                            ->where('announcement.resort_id', $this->resort_id)
                                                            ->whereIn('an.status', $statuses)
                                                            ->orderby('an.created_at', 'desc')
                                                            ->get(['announcement.*','an.status','an.id'])->map(function ($announcement) {
                                                              $employee    = Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                            ->where('employees.id', $announcement->employee_id)
                                                                            ->select('ra.first_name','ra.last_name')
                                                                            ->first();
                                                                return [
                                                                  'id'              => $announcement->id,
                                                                  'resort_id'       => $announcement->resort_id,
                                                                  'user_id'         => $announcement->employee_id,
                                                                  'message'         => $employee->first_name . ' ' . $employee->last_name . ' says Congratulation',
                                                                  'status'          => $announcement->status,
                                                                  'created_at'      => $announcement->created_at,
                                                                  'updated_at'      => $announcement->updated_at,
                                                                  'module'          => 'Announcement Wish',
                                                                  'page_id'         => 'announcement-detail',
                                                                  'type'            => 'You have a new message',
                                                                  'request_id'      => null,
                                                                  '_sort_key'       => $announcement->getRawOriginal('created_at'),
                                                                ];
                                                              });

        // Merge both collections and sort by created_at in descending order

        $merged                                         = collect(array_merge($notifications->all(), $Announcement->all()))
                                                          ->sortByDesc(function ($item) {
                                                          return \Carbon\Carbon::parse($item['_sort_key']);
                                                          })->values()
                                                          ->map(function ($item) {
                                                            unset($item['_sort_key']);
                                                            return $item;
                                                          });

        if ($merged->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No notifications found'], 200);
        }

        $response['status']                               =   true;
        $response['message']                              =   'Successfully fetched notifications';
        $response['notification_data']                    =   $merged;

      return response()->json($response);

    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  /**
   * POST notification/mark-read — the clear, single-purpose entry point
   * the ticket asked for (deleteMessageRead below already supported this
   * via module:'other', but that generic multi-purpose shape is easy to
   * get wrong client-side). Body: {"notification_id": 123}. Flips
   * unread -> read; never deletes the row, so it still shows up in
   * inactive-list for history/audit.
   */
  public function markRead(Request $request)
  {
    if (!Auth::guard('api')->check()) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $validator = \Validator::make($request->all(), [
      'notification_id' => 'required',
    ]);
    if ($validator->fails()) {
      return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
    }

    $notification = ResortNotification::where('id', $request->notification_id)
      ->where('resort_id', $this->resort_id)
      ->where('user_id', $this->user->GetEmployee->id)
      ->first();

    if (!$notification) {
      return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    if ($notification->status === 'unread') {
      $notification->status = 'read';
      $notification->save();
    }

    return response()->json(['success' => true, 'message' => 'Notification marked as read']);
  }

  public function deleteMessageRead(Request $request)
  {
      if (!Auth::guard('api')->check()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
      }

      try {

        if($request->module == 'Announcement Wish' && $request->has('notification_id') && $request->has('status')) {

            // notification_id was client-supplied with no ownership/resort
            // check — any authenticated user could flip the status on any
            // other resort's/employee's announcement notification.
            $announcement                               = AnnouncementNotification::where('id', $request->notification_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->where('employee_id', $this->user->GetEmployee->id)
                                                                ->first();
            if ($announcement) {
                $announcement->status                   = $request->status;
                $announcement->save();
                return response()->json([
                  'success'                             =>  true,
                  'message'                             =>  "Announcement {$request->status} Successfully"
                ]);
            }
        }

        if ($request->has('notification_id') && $request->has('status') && $request->module == 'other') {
            // Same IDOR pattern — scope to the caller's own resort + user.
            $notification                               =   ResortNotification::where('id', $request->notification_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->where('user_id', $this->user->GetEmployee->id)
                                                                ->first();
            if ($notification) {
                $notification->status                   =   $request->status;
                $notification->save();
                return response()->json([
                  'success'                             =>  true,
                  'message'                             =>  "Notification {$notification->status} Successfully"
                ]);
            }
            return response()->json(['success' => false, 'message' => 'Notification not found'], 200);
        }

        // Send birthday message if request_id and message are provided
        if ($request->has('request_id') && $request->has('message')) {

          // Find the user by request_id (assuming request_id is the employee
          // ID) — scoped to the caller's own resort, same IDOR fix already
          // applied to the other lookups in this method above.
          $user                                         =   Employee::where('id', $request->request_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->first();

          // Was commented out (one of the two variants had the wrong arg
          // count for the current sendMobileNotification signature) — the
          // birthday-wish reply never reached the recipient by any
          // mechanism.
          if ($user) {
              Common::sendMobileNotification($user->resort_id, 2, null, null, 'Birthday Wish', $request->message, 'Birthday', [$user->id], null);
          }

          return response()->json([
            'success'                                   =>  true,
            'message'                                   =>  'Notification Sent Successfully'
          ]);
        }

        // Clear all notifications for the user
        if ($request->module == 'delete_all') {

          ResortNotification::where('user_id', $this->user->GetEmployee->id)
            ->where('resort_id', $this->resort_id)
            ->update(['status' => 'deleted']);

              return response()->json([
                'success'                             =>  true,
                'message'                             =>  'Notification cleared Successfully'
              ]);
        }

         return response()->json(['success' => false, 'message' => 'Notification not found'], 200);
      } catch (\Exception $e) {
        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::error($e->getMessage());
        return response()->json(['success' => false, 'message' => 'Server eror'], 500);
      }
  }

}
