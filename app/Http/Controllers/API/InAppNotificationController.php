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
    if (!Auth::guard('api')->check()) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    try {
        $notifications                                  = ResortNotification::where('user_id', $this->user->GetEmployee->id)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('status', '!=', 'deleted')
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
                                                                  'type'            => $notification->type ?? null,
                                                                  'request_id'      => $notification->request_id ?? null,
                                                                ];
                                                              });

        $Announcement                                   = Announcement::join('announcement_notification as an','an.announcement_id','=','announcement.id')
                                                            ->where('announcement.employee_id',$this->user->GetEmployee->id)
                                                            ->where('announcement.resort_id', $this->resort_id)
                                                            ->where('an.status', '!=', 'deleted')
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
                                                                  'type'            => 'You have a new message',
                                                                  'request_id'      => null,
                                                                ];
                                                              });

        // Merge both collections and sort by created_at in descending order

        $merged                                         = collect(array_merge($notifications->all(), $Announcement->all()))
                                                          ->sortByDesc(function ($item) {
                                                          return \Carbon\Carbon::parse($item['created_at']);
                                                          })->values();

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

  public function deleteMessageRead(Request $request)
  {
      if (!Auth::guard('api')->check()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
      }

      try {

        if($request->module == 'Announcement Wish' && $request->has('notification_id') && $request->has('status')) {

            $announcement                               = AnnouncementNotification::find($request->notification_id);
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
            $notification                               =   ResortNotification::find($request->notification_id);
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

          // Find the user by request_id (assuming request_id is the employee ID)
          $user                                         =   Employee::find($request->request_id);

          // Common::sendMobileNotification($user->resort_id,2,null,null,'Birthday Wish',$request->message,'Birthday',[$user->id],null);
        //   Common::sendMobileNotification($user->resort_id,null,null,'Birthday Wish',$request->message,'Birthday',[$user->id],null);

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
