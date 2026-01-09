<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\AnnouncementNotification;
use App\Models\Announcement;
use App\Helpers\Common;
use DB;

class AnnouncementController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user                                 =   Auth::guard('api')->user();
            $this->resort_id                            =   $this->user->resort_id;
        }
    }

    public function announcementListing()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $announcement                               =   Announcement::join('employees as e', 'e.id', '=', 'announcement.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') 
                                                                ->join('announcement_category as ac', 'ac.id', '=', 'announcement.title')
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->where('announcement.resort_id', $this->resort_id)
                                                                ->where('announcement.status', 'Published')
                                                                ->select('announcement.*', 'e.Admin_Parent_id','ra.first_name','ra.last_name','ra.profile_picture','ac.name as category_name','rp.position_title')
                                                                ->orderBy('announcement.created_at', 'desc')
                                                                ->get()->map(function ($item) {
                                                                    $item->who_congratulate =  AnnouncementNotification::join('employees as e', 'e.id', '=', 'announcement_notification.employee_id')
                                                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                                                ->where('announcement_id', $item->id)
                                                                                                ->select('e.Admin_Parent_id', 'ra.first_name', 'ra.profile_picture')
                                                                                                ->get()->map(function ($congratulate) {
                                                                                                    $congratulate->profile_picture = Common::getResortUserPicture($congratulate->Admin_Parent_id);
                                                                                                    return $congratulate;
                                                                                                });
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                    return $item;
                                                                });
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Announcement dashboard retrieved successfully.',
                'data'                                  =>  $announcement
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function sendCongratulation($announcementId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $announcementId                                 =   base64_decode($announcementId);
        $employee                                       =   $this->user->GetEmployee;
        DB::beginTransaction();
        try {
            $announcement                               =   Announcement::find($announcementId);

            if (!$announcement) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Announcement not found.'], 200);
            }

            $AnnouncementNotification                   =   AnnouncementNotification::create([
                'resort_id'                             =>  $this->resort_id,
                'announcement_id'                       =>  $announcementId,
                'employee_id'                           =>  $employee->id
            ]);

            Common::sendMobileNotification($this->resort_id,3,null,$announcement->employee_id,'Congratulation','You have a new message.','Announcement',[$announcement->employee_id],null);

            DB::commit();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Congratulation message sent successfully.',
                'data'                                  =>  $AnnouncementNotification
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}