<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\Announcement;
use App\Models\AnnouncementCategory;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
use App\Events\ResortNotificationEvent;


class AnnouncementController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        $page_title ='Announcements List';
        $resort_id = $this->resort->resort_id;
        $categories = AnnouncementCategory::where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        if ($request->ajax()) {
            $query = Announcement::with(['employee.position','employee.resortAdmin','employee.department','category']);

            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('message', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('status', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('published_date', 'LIKE', "%{$searchTerm}%");
            
                    // Search employee name (first or last)
                    $q->orWhereHas('employee.resortAdmin', function ($adminQ) use ($searchTerm) {
                        $adminQ->where(function ($nameQ) use ($searchTerm) {
                            $nameQ->where('first_name', 'LIKE', "%{$searchTerm}%")
                                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                        });
                    });
            
                    // Search by employee ID
                    $q->orWhereHas('employee', function ($empQ) use ($searchTerm) {
                        $empQ->where('Emp_id', 'LIKE', "%{$searchTerm}%");
                    });
            
                    // Search by category name
                    $q->orWhereHas('category', function ($catQ) use ($searchTerm) {
                        $catQ->where('name', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
            
            // Show only archived if switch is ON, else show active
            if ($request->archived) {
                $query->where('archived', true);
            } else {
                $query->where('archived', false);
            }
            if($request->category){
                $query->where('title',$request->category);
            }
            if($request->status){
                $query->where('status',$request->status);
            }
            if ($request->department) {
                $query->whereHas('employee.department', function ($q) use ($request) {
                    $q->where('id', $request->department);
                });
            }
            if($request->date){
                $query->where('published_date',$request->date);
            }
            return datatables()->of($query)
                ->addColumn('title', function ($row) {
                    return $row->category->name ?? '-';
                })
                ->addColumn('employee_name', function ($row) {
                    $empName = $row->employee->resortAdmin->full_name ?? '-';
                    $image = Common::getResortUserPicture($row->employee->Admin_Parent_id ?? null);
                    return '<div class="tableUser-block">
                                <div class="img-circle">
                                    <img src="' . $image . '" alt="user">
                                </div>
                                <span class="userApplicants-btn">' . $empName . '</span>
                            </div>';
                })    
                ->addColumn('employee_id', function ($row) {
                    return ($row->employee->Emp_id ?? 'N/A');
                })
                ->addColumn('department_position', function ($row) {
                    $position = $row->employee->position->position_title ?? '—';
                    $department = $row->employee->department->name ?? '—';
                    return $department . ', ' . $position;
                })
                ->addColumn('published_date', function ($row) {
                    return $row->published_date ? Carbon::parse($row->published_date)->format('d M Y') : '-';
                })
                ->addColumn('status', function ($row) {
                    $badgeClass = match($row->status) {
                        'Published' => 'badge-themeSuccess',
                        'Draft' => 'badge-infoBorder',
                        'Scheduled' => 'badge-blue',
                        default => 'badge-secondary'
                    };
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('actions', function ($row) use ($request) {
                    if ($request->archived) {
                        // Show Restore button for archived announcements
                        return '
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green restore-announcement" data-id="' . $row->id . '">
                                <i class="fas fa-undo text-white"></i>
                            </a>
                        ';
                    } else {
                        // Show View, Edit, Archive buttons for active announcements
                        $viewBtn = '
                            <a href="' . route("people.announcements.view", base64_encode($row->id)) . '" class="btn-lg-icon icon-bg-skyblue">
                                <img src="' . asset("resorts_assets/images/eye.svg") . '" alt="View" class="img-fluid">
                            </a>
                        ';

                        $editBtn = '';
                        if (in_array($row->status, ['Draft', 'Scheduled'])) {
                            $editBtn = '
                                <a href="' . route("people.announcements.edit", base64_encode($row->id)) . '" class="btn-lg-icon icon-bg-blue">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                            ';
                        }

                        $archiveBtn = '
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-yellow archive-announcement" data-id="' . $row->id . '">
                                <img src="' . asset("resorts_assets/images/archive-yellow.svg") . '" alt="Archive" class="img-fluid">
                            </a>
                        ';
                        return $viewBtn . $editBtn . $archiveBtn;

                    }
                })
                ->rawColumns(['status', 'actions','employee_name'])
                ->make(true);
        }
        return view('resorts.people.announcement.list',compact('page_title','categories','departments'));
    }

    public function create()
    {
        $page_title ='Create Announcement';
        $categories = AnnouncementCategory::where('resort_id',$this->resort->resort_id)->get();
        $employees = Employee::with('resortAdmin')->where('resort_id',$this->resort->resort_id)->whereIn('status',['Active','OnLeave','Probationary','Resigned','contractual'])->get();
        return view('resorts.people.announcement.create',compact('page_title','categories','employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'announcement_title' => 'required|exists:announcement_category,id',
            'employee_name' => 'required|exists:employees,id',
            'congratulatory_message' => 'required|string',
            'action_type' => 'required|in:Draft,Scheduled,Published',
            'published_date' => 'nullable|date',
        ]);

        $announcement = new Announcement();
        $announcement->resort_id = $this->resort->resort_id;
        $announcement->title = $request->announcement_title;
        $announcement->employee_id = $request->employee_name;
        $announcement->message = $request->congratulatory_message;
        $announcement->status = $request->action_type;

        if ($request->action_type === 'Scheduled' && $request->published_date) {
            $announcement->published_date = Carbon::parse($request->published_date)->format('Y-m-d');
        } elseif ($request->action_type === 'Published') {
            $announcement->published_date = now();
        } else {
            $announcement->published_date = null;
        }

        $announcement->save();

        // // 🔔 Send notification only for Published and Scheduled
        if (in_array($announcement->status, ['Published'])) {
            $employee = Employee::findOrFail($announcement->employee_id);
            $title = 'New Announcement: ' . $announcement->category->name;
            $msg ='Congratulations ' . $announcement->employee->resortAdmin->full_name . '! ' . $announcement->message;
            $moduleName = "People - Announcement";

            $deviceToken = $employee->device_token;
            $body = $msg;

            
            Common::sendPushNotifictionForMobile([$deviceToken], $title, $body, $moduleName, NULL,NULL,NULL,NULL); 
            
            event(new ResortNotificationEvent(Common::nofitication(
                $announcement->resort_id,
                10,
                $title,
                $msg,
                '0',
                $announcement->employee_id,
                $moduleName
            )));
        }
        

        return response()->json([
            'success' => true,
            'message' => 'Announcement has been successfully saved and scheduled as ' . $request->action_type,
            'redirect_url'=>route('people.announcements')
        ]);
    }

    public function archive(Request $request)
    {
        $announcement = Announcement::find($request->id);

        if (!$announcement) {
            return response()->json(['status' => false, 'message' => 'Announcement not found.']);
        }

        $announcement->archived = true; // Make sure 'archived' column exists in your DB
        $announcement->save();

        return response()->json(['status' => true, 'message' => 'Announcement archived successfully.']);
    }

    public function restore(Request $request)
    {
        $announcement = Announcement::findOrFail($request->id);
        $announcement->archived = false;
        $announcement->save();

        return response()->json([
            'status' => true,
            'message' => 'Announcement restored successfully.',
        ]);
    }

    public function edit($id)
    {
        $page_title ='Edit Announcement';
        $categories = AnnouncementCategory::where('resort_id',$this->resort->resort_id)->get();
        $employees = Employee::with('resortAdmin')->where('resort_id',$this->resort->resort_id)->whereIn('status',['Active','OnLeave','Probationary','Resigned','contractual'])->get();
        $announcement = Announcement::findOrFail(base64_decode($id));

        return view('resorts.people.announcement.edit', compact('announcement','page_title','categories','employees'));
    }

    public function update(Request $request, $id)
    {
        $announcementId = base64_decode($id); // Decode the ID
        $announcement = Announcement::findOrFail($announcementId);

        // Validate the request
        $validated = $request->validate([
            'announcement_title' => 'required|exists:announcement_category,id',
            'employee_name' => 'required|exists:employees,id',
            'congratulatory_message' => 'required|string',
            'action_type' => 'required|in:Draft,Scheduled,Published',
            'published_date' => 'nullable|date'
        ]);

        // Update the announcement
        $announcement->title = $validated['announcement_title'];
        $announcement->employee_id = $validated['employee_name'];
        $announcement->message = $validated['congratulatory_message'];
        $announcement->status = $validated['action_type'];

        // Set published_date only if action is Scheduled
        if ($validated['action_type'] === 'Scheduled') {
            $announcement->published_date = Carbon::parse($validated['published_date'])->format('Y-m-d');
        } elseif ($validated['action_type'] === 'Published') {
            $announcement->published_date = now(); // set to current timestamp
        } else {
            $announcement->published_date = null;
        }

        $announcement->save();

         // 🔔 Send notification only for Published and Scheduled
        if (in_array($announcement->status, ['Published'])) {
            $employee = Employee::findOrFail($announcement->employee_id);

            $title = 'New Announcement: ' . $announcement->category->name;
            $msg = 'Congratulations ' . $announcement->employee->resortAdmin->full_name . '! ' . $announcement->message;
            $moduleName = "People - Announcement";

            $deviceToken = $employee->device_token;
            $body = $msg;

            Common::sendPushNotifictionForMobile([$deviceToken], $title, $body, $moduleName,NULL,NULL,NULL,NULL);

            event(new ResortNotificationEvent(Common::nofitication(
                $announcement->resort_id,
                10,
                $title,
                $msg,
                $announcement->employee_id,
                $moduleName
            )));
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement saved successfully as ' . $request->action_type,
            'redirect_url'=>route('people.announcements')
        ]);
    }

    public function view($id)
    {
        $page_title ='Announcement Details';

        $announcement = Announcement::with(['category','employee.resortAdmin','employee.position','employee.department'])->where('id',base64_decode($id))->first();
        return view('resorts.people.announcement.detail', compact('announcement','page_title'));
    }


    public function getEmployeeDetails(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $employee = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('id', $employeeId)
            ->first();
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found.']);
        }
        $image = Common::getResortUserPicture($employee->Admin_Parent_id ?? null);
        $employeeDetails = [
            'full_name' => $employee->resortAdmin->full_name,
            'position' => $employee->position->position_title ?? '—',
            'department' => $employee->department->name ?? '—',
            'profile_picture' => $image,
            'emp_id' => $employee->Emp_id ?? '—'
        ];
        return response()->json(['success' => true, 'data' => $employeeDetails]);
    }

}