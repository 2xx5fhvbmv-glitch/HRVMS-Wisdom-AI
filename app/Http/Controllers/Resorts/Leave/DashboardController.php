<?php

namespace App\Http\Controllers\Resorts\Leave;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use App\Models\ResortHoliday;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveStatus;
use App\Models\ResortDepartment;
use App\Models\LeaveCategory;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        // $this->resort = Auth::guard('resort-admin')->user();
        // $this->reporting_to = $this->resort->GetEmployee->id;
        // // dd($reporting_to);
        // $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if (!$this->resort) {
            return;
        }
        if (isset($this->resort->GetEmployee) && $this->resort->GetEmployee) {
            $this->reporting_to = $this->resort->GetEmployee->id;
        } else {
            $this->reporting_to = $this->resort->id ?? null;
        }
        $this->underEmp_id = $this->reporting_to ? Common::getSubordinates($this->reporting_to) : [];

    }

    public function admin_dashboard()
    {
        $page_title ='Leave';
        $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();
        $upcomingHolidays = ResortHoliday::where('resort_id', $this->resort->resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->whereRaw('YEAR(PublicHolidaydate) >= ?', [Carbon::now()->year])
        ->orderBy('PublicHolidaydate', 'asc')
        ->get();

        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('name', 'Human Resources')->value('id');
        $loggedInEmployee = $this->resort->getEmployee ?? $this->resort->GetEmployee ?? null;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;
        $userDeptId = $loggedInEmployee->Dept_id ?? null;
        $isFromHRDepartment = $hrDeptId && $userDeptId && (int) $userDeptId === (int) $hrDeptId;

        if ($isFromHRDepartment) {
            $total_applied_leaves = DB::table('employees_leaves as el')
                ->where('el.resort_id', $this->resort->resort_id)->where('flag', null)->count();
            $total_approved_leaves = DB::table('employees_leaves as el')
                ->where('el.resort_id', $this->resort->resort_id)->where('flag', null)->where('status', 'Approved')->count();
            $total_pending_leaves = DB::table('employees_leaves as el')
                ->where('el.resort_id', $this->resort->resort_id)->where('flag', null)->where('status', 'Pending')->count();
            $total_rejected_leaves = DB::table('employees_leaves as el')
                ->where('el.resort_id', $this->resort->resort_id)->where('flag', null)->where('status', 'Rejected')->count();
        } else {
            $total_applied_leaves = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')->where('flag', null)
                ->where('el.resort_id', $this->resort->resort_id)
                ->when($userDeptId, function ($q) use ($userDeptId, $loggedInEmployeeId) {
                    $q->where(function ($q2) use ($userDeptId, $loggedInEmployeeId) {
                        $q2->where('e.Dept_id', $userDeptId)->orWhere('el.emp_id', $loggedInEmployeeId);
                    });
                })
                ->when(!$userDeptId, function ($q) use ($loggedInEmployeeId) {
                    $q->where('e.reporting_to', $this->reporting_to)->orWhere('el.emp_id', $loggedInEmployeeId);
                })
                ->count();

            $baseNonHR = function ($status = null) use ($userDeptId, $loggedInEmployeeId) {
                $q = DB::table('employees_leaves as el')
                    ->join('employees as e', 'e.id', '=', 'el.emp_id')
                    ->where('flag', null)
                    ->where('el.resort_id', $this->resort->resort_id);
                if ($userDeptId) {
                    $q->where(function ($q2) use ($userDeptId, $loggedInEmployeeId) {
                        $q2->where('e.Dept_id', $userDeptId)->orWhere('el.emp_id', $loggedInEmployeeId);
                    });
                } else {
                    $q->where(function ($q2) use ($loggedInEmployeeId) {
                        $q2->where('e.reporting_to', $this->reporting_to)->orWhere('el.emp_id', $loggedInEmployeeId);
                    });
                }
                if ($status) {
                    $q->where('el.status', $status);
                }
                return $q->count();
            };
            $total_approved_leaves = $baseNonHR('Approved');
            $total_pending_leaves = $baseNonHR('Pending');
            $total_rejected_leaves = $baseNonHR('Rejected');
        }

        // Get today's and tomorrow's month-day (dob is stored as Y-m-d, so compare using m-d)
        $todayMd = Carbon::today()->format('m-d');
        $tomorrowMd = Carbon::tomorrow()->format('m-d');
       
        $upcomingBirthdays = Employee::with(['resortAdmin', 'position'])
        ->where('resort_id', $this->resort->resort_id)
        ->whereNotNull('dob')
        ->where('dob', '!=', '')
        ->where(function ($q) use ($todayMd, $tomorrowMd) {
            $q->whereRaw('SUBSTRING(dob, 6, 5) = ?', [$todayMd])   // Y-m-d: positions 6-10 = mm-dd
              ->orWhereRaw('SUBSTRING(dob, 6, 5) = ?', [$tomorrowMd]);
        })
        ->get();

        $upcomingBirthdays = $upcomingBirthdays->map(function($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });
       
        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($todayMd) {
            return substr($employee->dob, 5, 5) === $todayMd; // 1990-02-25 -> 02-25
        });

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrowMd) {
            return substr($employee->dob, 5, 5) === $tomorrowMd;
        });

        $show_department_filter = $isFromHRDepartment;
        return view('resorts.leaves.dashboard.admin-dashboard',compact('page_title','upcomingHolidays','todayBirthdays','tomorrowBirthdays','total_applied_leaves','resort_departments','total_approved_leaves','total_pending_leaves','total_rejected_leaves','show_department_filter'));
    }

    public function HR_Dashobard()
    {
        $page_title ='Leave';
        $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();
        $upcomingHolidays = ResortHoliday::where('resort_id', $this->resort->resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->whereRaw('YEAR(PublicHolidaydate) >= ?', [Carbon::now()->year])
        ->orderBy('PublicHolidaydate', 'asc')
        ->get();
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $employeeRankPosition = Common::getEmployeeRankPosition($loggedInEmployee);
        $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");
        $isHRExcom = ($available_rank === "EXCOM");

        // Human Resources HOD should see HR/EXCOM-level leave data
        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('name', 'Human Resources')
            ->value('id');
        $isHRDeptHOD = $isHOD && $loggedInEmployee && $hrDeptId && (int)$loggedInEmployee->Dept_id === (int)$hrDeptId;
        if ($isHRDeptHOD) {
            $isHR = true;
            $isHOD = false;
        }
        $userDeptId = $loggedInEmployee->Dept_id ?? null;
        $isFromHRDepartment = $hrDeptId && $userDeptId && (int)$userDeptId === (int)$hrDeptId;
        $show_department_filter = $isFromHRDepartment;

        $canViewWholeResort = $isHR || $isHRExcom || $isGM;
        if( $canViewWholeResort ){
            $total_applied_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->count();
            $total_approved_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Approved')->count();
            $total_pending_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Pending')->count();
            $total_rejected_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Rejected')->count();
        }
        else{
            $total_applied_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')->where('flag',null)
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->where(function ($q) use ($loggedInEmployeeId) {
                $q->where('els.approver_id',$loggedInEmployeeId)->orWhere('el.emp_id', $loggedInEmployeeId);
            })
            ->where('el.resort_id', $this->resort->resort_id)->count();

            $total_approved_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->where(function ($q) use ($loggedInEmployeeId) {
                $q->where('els.approver_id',$loggedInEmployeeId)->orWhere('el.emp_id', $loggedInEmployeeId);
            })
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Approved')->count();

            $total_pending_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->where(function ($q) use ($loggedInEmployeeId) {
                $q->where('els.approver_id',$loggedInEmployeeId)->orWhere('el.emp_id', $loggedInEmployeeId);
            })
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Pending')->count();

            $total_rejected_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->where(function ($q) use ($loggedInEmployeeId) {
                $q->where('els.approver_id',$loggedInEmployeeId)->orWhere('el.emp_id', $loggedInEmployeeId);
            })
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Rejected')->count();
        }

        // Get today's and tomorrow's month-day (dob is stored as Y-m-d)
        $todayMd = Carbon::today()->format('m-d');
        $tomorrowMd = Carbon::tomorrow()->format('m-d');
       
        $upcomingBirthdays = Employee::with(['resortAdmin', 'position'])
        ->where('resort_id', $this->resort->resort_id)
        ->whereNotNull('dob')
        ->where('dob', '!=', '')
        ->where(function ($q) use ($todayMd, $tomorrowMd) {
            $q->whereRaw('SUBSTRING(dob, 6, 5) = ?', [$todayMd])   // Y-m-d: positions 6-10 = mm-dd
              ->orWhereRaw('SUBSTRING(dob, 6, 5) = ?', [$tomorrowMd]);
        })
        ->get();

        $upcomingBirthdays = $upcomingBirthdays->map(function($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });
       
        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($todayMd) {
            return substr($employee->dob, 5, 5) === $todayMd;
        });

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrowMd) {
            return substr($employee->dob, 5, 5) === $tomorrowMd;
        });

        return view('resorts.leaves.dashboard.hrdashboard',compact('page_title','upcomingHolidays','todayBirthdays','tomorrowBirthdays','total_applied_leaves','resort_departments','total_approved_leaves','total_pending_leaves','total_rejected_leaves','show_department_filter'));
    }

    public function hod_dashboard()
    {
        $page_title = 'Leave';
        $loggedInEmployee = $this->resort->getEmployee ?? $this->resort->GetEmployee ?? null;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;
        $hodDeptId = $loggedInEmployee->Dept_id ?? null;
        $resort_departments = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('status', 'active')->get();

        $upcomingHolidays = ResortHoliday::where('resort_id', $this->resort->resort_id)
            ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
            ->whereRaw('YEAR(PublicHolidaydate) >= ?', [Carbon::now()->year])
            ->orderBy('PublicHolidaydate', 'asc')
            ->get();

        // Only HR department sees whole resort; other departments see only their department
        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('name', 'Human Resources')->value('id');
        $isFromHRDepartment = $hrDeptId && $hodDeptId && (int) $hodDeptId === (int) $hrDeptId;

        $baseLeaveQuery = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->where('el.resort_id', $this->resort->resort_id)
            ->whereNull('el.flag');

        if ($isFromHRDepartment) {
            // HR department: whole resort
        } elseif ($hodDeptId) {
            $baseLeaveQuery->where(function ($q) use ($hodDeptId, $loggedInEmployeeId) {
                $q->where('e.Dept_id', $hodDeptId)->orWhere('el.emp_id', $loggedInEmployeeId);
            });
        } else {
            $baseLeaveQuery->where(function ($q) use ($loggedInEmployeeId) {
                $q->where('e.reporting_to', $loggedInEmployeeId)->orWhere('el.emp_id', $loggedInEmployeeId);
            });
        }

        $total_applied_leaves = (clone $baseLeaveQuery)->count();

        $total_approved_leaves = (clone $baseLeaveQuery)->where('el.status', 'Approved')->count();

        $total_pending_leaves = (clone $baseLeaveQuery)->where('el.status', 'Pending')->count();

        $total_rejected_leaves = (clone $baseLeaveQuery)->where('el.status', 'Rejected')->count();

        $show_department_filter = $isFromHRDepartment;

        // Today's and tomorrow's birthdays (same as HR dashboard)
        $todayMd = Carbon::today()->format('m-d');
        $tomorrowMd = Carbon::tomorrow()->format('m-d');
        $upcomingBirthdays = Employee::with(['resortAdmin', 'position'])
            ->where('resort_id', $this->resort->resort_id)
            ->whereNotNull('dob')
            ->where('dob', '!=', '')
            ->where(function ($q) use ($todayMd, $tomorrowMd) {
                $q->whereRaw('SUBSTRING(dob, 6, 5) = ?', [$todayMd])
                    ->orWhereRaw('SUBSTRING(dob, 6, 5) = ?', [$tomorrowMd]);
            })
            ->get();
        $upcomingBirthdays = $upcomingBirthdays->map(function ($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });
        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($todayMd) {
            return substr($employee->dob, 5, 5) === $todayMd;
        });
        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrowMd) {
            return substr($employee->dob, 5, 5) === $tomorrowMd;
        });

        return view('resorts.leaves.dashboard.hoddashboard', compact('page_title', 'upcomingHolidays', 'todayBirthdays', 'tomorrowBirthdays', 'resort_departments', 'total_applied_leaves', 'total_approved_leaves', 'total_pending_leaves', 'total_rejected_leaves', 'show_department_filter'));
    }

    public function get_upcomimg_holidays(Request $request){
        $resort_id = $this->resort->resort_id;
        $upcomingHolidays = ResortHoliday::where('resort_id', $resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->whereRaw('YEAR(PublicHolidaydate) >= ?', [Carbon::now()->year])
        ->orderBy('PublicHolidaydate', 'ASC')
        ->get();

        if ($request->ajax()) {
            $query = ResortHoliday::where('resort_id', $resort_id)
                ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
                ->whereRaw('YEAR(PublicHolidaydate) >= ?', [Carbon::now()->year]);

            return datatables()->of($query)
                ->order(function ($q) use ($request) {
                    if ($request->has('order') && $request->has('columns')) {
                        $order = $request->input('order')[0];
                        $colIndex = (int) $order['column'];
                        $dir = strtolower($order['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                        if ($colIndex === 0 || $colIndex === 1) {
                            $q->orderBy('PublicHolidaydate', $dir);
                        } elseif ($colIndex === 2) {
                            $q->orderBy('PublicHolidayName', $dir);
                        } else {
                            $q->orderBy('PublicHolidaydate', 'asc');
                        }
                    } else {
                        $q->orderBy('PublicHolidaydate', 'asc');
                    }
                })
                ->editColumn('PublicHolidaydate', function ($row) {
                    return $row->PublicHolidaydate ? Carbon::parse($row->PublicHolidaydate)->format('d M Y')  : '--';
                })
                ->addColumn('day', function ($row) {
                    return $row->PublicHolidaydate ? Carbon::parse($row->PublicHolidaydate)->format('l') : '--';
                })
                ->make(true);
        }

       $page_title = "Holidays";
       
       return view('resorts.leaves.dashboard.upcoming_holidays', compact('page_title','resort_id'));
    }

    public function getUpcomingBirthdaysList(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $todayDate = Carbon::today();
        $today = $todayDate->format('d-m');
        $tomorrow = Carbon::tomorrow()->format('d-m');
        $todayMd = $todayDate->format('m-d');
        $tomorrowMd = Carbon::tomorrow()->format('m-d');

        // dob is stored as Y-m-d (e.g. 1990-02-25). SUBSTRING(dob, 6, 5) = mm-dd
        $upcomingBirthdays = Employee::with(['resortAdmin', 'position'])
            ->where('resort_id', $resort_id)
            ->whereNotNull('dob')
            ->where('dob', '!=', '')
            ->get();

        $upcomingBirthdays = $upcomingBirthdays->filter(function ($employee) {
            $d = $employee->dob;
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || preg_match('/^\d{2}-\d{2}-\d{4}$/', $d);
        })->map(function ($employee) use ($todayDate) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            $dob = null;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee->dob)) {
                $dob = Carbon::createFromFormat('Y-m-d', $employee->dob);
            } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $employee->dob)) {
                $dob = Carbon::createFromFormat('d-m-Y', $employee->dob);
            }
            if ($dob) {
                $employee->formatted_dob = $dob->format('l M, d');
                $nextBirthday = Carbon::createFromDate($todayDate->year, $dob->month, $dob->day);
                if ($nextBirthday->lt($todayDate)) {
                    $nextBirthday->addYear();
                }
                $employee->next_birthday_date = $nextBirthday;
            } else {
                $employee->formatted_dob = 'Invalid Date';
                $employee->next_birthday_date = null;
            }
            return $employee;
        })->filter(function ($employee) {
            return $employee->next_birthday_date !== null;
        });

        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($todayMd, $today) {
            $d = $employee->dob;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                return substr($d, 5, 5) === $todayMd;
            }
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $d)) {
                return substr($d, 0, 5) === $today;
            }
            return false;
        })->values();

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrowMd, $tomorrow) {
            $d = $employee->dob;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                return substr($d, 5, 5) === $tomorrowMd;
            }
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $d)) {
                return substr($d, 0, 5) === $tomorrow;
            }
            return false;
        })->values();

        // Remaining: next birthday >= today, exclude today/tomorrow, sorted by next birthday
        $remainingBirthdays = $upcomingBirthdays->filter(function ($employee) use ($todayMd, $tomorrowMd, $today, $tomorrow) {
            $d = $employee->dob;
            $isToday = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && substr($d, 5, 5) === $todayMd)
                || (preg_match('/^\d{2}-\d{2}-\d{4}$/', $d) && substr($d, 0, 5) === $today);
            $isTomorrow = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && substr($d, 5, 5) === $tomorrowMd)
                || (preg_match('/^\d{2}-\d{2}-\d{4}$/', $d) && substr($d, 0, 5) === $tomorrow);
            if ($isToday || $isTomorrow) {
                return false;
            }
            return $employee->next_birthday_date && $employee->next_birthday_date->gte(Carbon::today());
        })->sortBy(function ($e) {
            return $e->next_birthday_date->format('Y-m-d');
        })->values();

        $page_title = "Upcoming Birthdays";
        return view('resorts.leaves.dashboard.upcoming_birthdays', compact(
            'page_title',
            'resort_id',
            'todayBirthdays',
            'tomorrowBirthdays',
            'remainingBirthdays',
            'today',
            'tomorrow'
        ));
    }

    // public function getLeaveRequests(Request $request) {
    //     // Initialize variables
    //     $resort_id = $request->user()->resort_id;
    //     $loggedInEmployee = $this->resort->getEmployee;
    //     $loggedInEmployeeId = $loggedInEmployee->id ?? null;
    //     // dd($loggedInEmployeeId);
    //     $department_id = $request->get('department_id', '');
    //     $position_id = $request->get('position_id', '');
    //     $search = $request->get('search', '');
    //     $rank = config('settings.Position_Rank');
    //     $current_rank = $this->resort->getEmployee->rank ?? null;
    //     $available_rank = $rank[$current_rank] ?? '';

    //     $isHOD = ($available_rank === "HOD");
    //     $isHR = ($available_rank === "HR");

    //     $permission = '';
    //     if(Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false){
    //         $permission ='d-none';
    //     }
    //     // Base query for leave requests
    //     $leave_requests_query = DB::table('employees_leaves as el')
    //         ->join('employees as e', 'e.id', '=', 'el.emp_id')
    //         ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
    //         ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
    //         ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
    //         ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
    //         // ->leftJoin('employees_leaves_status as els', function($join) {
    //         //     $join->on('els.leave_request_id', '=', 'el.id')
    //         //          ->whereRaw('els.id = (SELECT MAX(id) FROM employees_leaves_status WHERE leave_request_id = el.id AND status != "Pending")');
    //         // })
    //          ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
    //         ->where('el.resort_id', $resort_id)
    //         ->where('el.flag', null)
    //         ->where('el.status', 'Pending');
        
    //     $leave_requests_query->where(function ($query) use ($loggedInEmployeeId) {
    //         $query->where('els.approver_id', $loggedInEmployeeId)
    //                 ->where('els.status', 'Pending');
    //                 // ->orWhere('el.emp_id', $loggedInEmployeeId); // Include logged-in user's own leave applications
    //     });

    //       // Apply filters
    //     if (isset($department_id) && !empty($department_id)) {
    //         $leave_requests_query->where('e.Dept_id', $department_id);
    //     }
    //     if ($position_id) {
    //         $leave_requests_query->where('e.Position_id', $position_id);
    //     }
    //     if ($search) {
    //         $leave_requests_query->where(function ($q) use ($search) {
    //             $q->where('ra.first_name', 'LIKE', '%' . $search . '%')
    //               ->orWhere('ra.last_name', 'LIKE', '%' . $search . '%')
    //               ->orWhere('e.Emp_id', 'LIKE', '%' . $search . '%');
    //         });
    //     }
    
    //     // Get total records
    //     $total_records = $leave_requests_query->count();
          
    //     // Apply pagination
    //     $leave_requests = $leave_requests_query
    //         ->groupBy('el.id')
    //         ->orderBy('el.created_at','desc')
    //         ->select(
    //             'el.*',
    //             'el.Emp_id as employee_id',
    //             'e.Admin_Parent_id',
    //             'ra.first_name',
    //             'ra.last_name',
    //             'lc.leave_type',
    //             'lc.color',
    //             'rp.position_title as position',
    //             'rd.code as code',
    //             'rd.name as department',
    //             'els.status as last_status',
    //             'els.approver_rank as approver_rank',
    //             'el.from_date',
    //             'el.to_date',
    //             'el.total_days',
    //             'el.attachments',
    //             'el.created_at',
    //             'els.status as approval_status',
    //             'els.approver_id as approver_id',
    //         )
    //         ->skip($request->get('start', 0))
    //         ->take($request->get('length', 10))
    //         ->get();
    //     // Format results
    //     $leave_requests = $leave_requests->map(function($leaveRequest) {
    //         $leaveRequest->from_date = Carbon::parse($leaveRequest->from_date)->format('d M');
    //         $leaveRequest->to_date = Carbon::parse($leaveRequest->to_date)->format('d M');
    //         $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);
    //         $leaveRequest->combinedLeave = EmployeeLeave::where('flag',$leaveRequest->id)
    //         ->join('leave_categories as lc','lc.id','=','employees_leaves.leave_category_id')
    //         ->first();
    
    //         // Set readable status
    //         $role = ucfirst(strtolower($leaveRequest->approver_rank ?? ''));

    //         $rank = config('settings.Position_Rank');
    //         $role = $rank[$role] ?? '';
    //         $leaveRequest->to_date =  (isset($leaveRequest->combinedLeave) ? $leaveRequest->combinedLeave->to_date :$leaveRequest->to_date);
    //         $leaveRequest->to_date = Carbon::parse($leaveRequest->to_date)->format('d M');
    //         $leaveRequest->status_text = $leaveRequest->last_status ? "{$leaveRequest->last_status} by {$role}" : 'Pending';
    //         $leaveRequest->total_days = (isset($leaveRequest->combinedLeave) ? ($leaveRequest->combinedLeave->total_days + $leaveRequest->total_days) :$leaveRequest->total_days);
    //         $leaveRequest->routes = route('leave.details',base64_encode($leaveRequest->id));
    //         return $leaveRequest;
    //     });
    
    //     // Return response in DataTables format
    //     return response()->json([
    //         'draw' => $request->get('draw', 1),
    //         'recordsTotal' => $total_records,
    //         'recordsFiltered' => $total_records,
    //         'data' => $leave_requests,
    //         'permission' => $permission,

    //     ]);
    // }

    public function getLeaveRequests(Request $request)
    {
        $resort_id = $request->user()->resort_id;
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;

        $department_id = $request->get('department_id', '');
        $position_id = $request->get('position_id', '');
        $search = $request->get('search', '');
        $filterYear = (int) ($request->get('year') ?: Carbon::now()->year);
        $yearStart = Carbon::createFromDate($filterYear, 1, 1)->format('Y-m-d');
        $yearEnd = Carbon::createFromDate($filterYear, 12, 31)->format('Y-m-d');
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $employeeRankPosition = Common::getEmployeeRankPosition($loggedInEmployee);
        $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
        $isHOD = ($available_rank === 'HOD');

        // Human Resources HOD should see HR/EXCOM-level leave data
        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('name', 'Human Resources')
            ->value('id');
        $isHR = ($available_rank === 'HR');
        $isHRExcom = ($available_rank === 'EXCOM');
        $isHRDeptHOD = $isHOD && $loggedInEmployee && $hrDeptId && (int)$loggedInEmployee->Dept_id === (int)$hrDeptId;
        if ($isHRDeptHOD) {
            $isHR = true;
            $isHOD = false;
        }

        // HR department, EXCOM and GM see all departments; others see only their own
        $userDeptId = $loggedInEmployee->Dept_id ?? null;
        $isFromHRDepartment = $hrDeptId && $userDeptId && (int) $userDeptId === (int) $hrDeptId;
        $canViewWholeResort = $isFromHRDepartment || $isHRExcom || $isGM;
        $hodDeptId = $loggedInEmployee->Dept_id ?? null;

        $permission = Common::checkRouteWisePermission('leave.request', config('settings.resort_permissions.edit')) ? '' : 'd-none';

        if ($canViewWholeResort) {
            // Whole resort: one row per leave, no join on status (status comes from statusesGrouped in map); include self leave
            $leave_requests_query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->where('el.resort_id', $resort_id)
                ->where('el.flag', null)
                ->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('el.from_date', '<=', $yearEnd)->where('el.to_date', '>=', $yearStart);
                });
        } elseif ($isHOD) {
            // HOD: see department leaves (or direct reportees), include own leave in list
            $leave_requests_query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->where('el.resort_id', $resort_id)
                ->where('el.flag', null)
                ->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('el.from_date', '<=', $yearEnd)->where('el.to_date', '>=', $yearStart);
                });
            if ($hodDeptId) {
                $leave_requests_query->where(function ($q) use ($hodDeptId, $loggedInEmployeeId) {
                    $q->where('e.Dept_id', $hodDeptId)->orWhere('el.emp_id', $loggedInEmployeeId);
                });
            } else {
                $leave_requests_query->where(function ($q) use ($loggedInEmployeeId) {
                    $q->where('e.reporting_to', $loggedInEmployeeId)->orWhere('el.emp_id', $loggedInEmployeeId);
                });
            }
        } else {
            // Leaves where current user is applicant OR has a Pending approver row; only same department (other depts see only their data)
            $leaveIds = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->where('el.resort_id', $resort_id)
                ->whereNull('el.flag')
                ->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('el.from_date', '<=', $yearEnd)->where('el.to_date', '>=', $yearStart);
                })
                ->when($userDeptId, function ($q) use ($userDeptId) {
                    $q->where('e.Dept_id', $userDeptId);
                })
                ->where(function ($q) use ($loggedInEmployeeId) {
                    $q->where('el.emp_id', $loggedInEmployeeId)
                        ->orWhereIn('el.id', function ($sub) use ($loggedInEmployeeId) {
                            $sub->select('leave_request_id')
                                ->from('employees_leaves_status')
                                ->where('approver_id', $loggedInEmployeeId)
                                ->where('status', 'Pending');
                        });
                })
                ->pluck('el.id')
                ->unique()
                ->values()
                ->toArray();

            $leave_requests_query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->where('el.resort_id', $resort_id)
                ->where('el.flag', null)
                ->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('el.from_date', '<=', $yearEnd)->where('el.to_date', '>=', $yearStart);
                })
                ->whereIn('el.id', $leaveIds ?: [0]);
        }

        // Department filter: only allow if user can view whole resort or is filtering within their own department
        if (!empty($department_id) && ($canViewWholeResort || (int) $department_id === (int) $userDeptId)) {
            $leave_requests_query->where('e.Dept_id', $department_id);
        }
        if ($position_id) {
            $leave_requests_query->where('e.Position_id', $position_id);
        }
        if ($search) {
            $leave_requests_query->where(function ($q) use ($search) {
                $q->where('ra.first_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('ra.last_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('e.Emp_id', 'LIKE', '%' . $search . '%');
            });
        }

        $total_records = $leave_requests_query->count();

        $selectColumns = [
            'el.*',
            'e.Emp_id as employee_id',
            'e.Admin_Parent_id',
            'e.reporting_to as reporting_to',
            'e.rank as applicant_rank',
            'ra.first_name',
            'ra.last_name',
            'lc.leave_type',
            'lc.color',
            'rp.position_title as position',
            'rd.code as code',
            'rd.name as department',
            'el.from_date',
            'el.to_date',
            'el.total_days',
            'el.attachments',
            'el.created_at',
        ];
        // Status columns come from statusesGrouped in map; use NULL here
        $selectColumns[] = DB::raw('NULL as last_status');
        $selectColumns[] = DB::raw('NULL as approver_rank');
        $selectColumns[] = DB::raw('NULL as approval_status');
        $selectColumns[] = DB::raw('NULL as approver_id');

        $leave_requests = $leave_requests_query
            ->orderBy('el.created_at', 'desc')
            ->select($selectColumns)
            ->skip($request->get('start', 0))
            ->take($request->get('length', 10))
            ->get();

        // Fetch all statuses once for performance
        $statusesGrouped = DB::table('employees_leaves_status')
            ->whereIn('leave_request_id', $leave_requests->pluck('id'))
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('leave_request_id');

        // Build approver_id => role label from employees.rank so "Approved by HOD" shows correctly (even when approver_rank on status row is wrong/null)
        $approverIds = collect($statusesGrouped)->flatten(1)->pluck('approver_id')->filter()->unique()->values()->all();
        $approverIdToLabel = [];
        if (!empty($approverIds)) {
            $approverRanks = Employee::whereIn('id', $approverIds)->pluck('rank', 'id');
            foreach ($approverRanks as $empId => $r) {
                $k = $r !== null && $r !== '' ? (string) $r : null;
                $approverIdToLabel[(int) $empId] = ($k !== null && array_key_exists($k, $rank)) ? $rank[$k] : 'designated approver';
            }
        }

        $loggedInRankStr = trim((string)($loggedInEmployee->rank ?? ''));
        // Can approve: (1) applicant's reporting_to, or (2) applicant is GM (rank 8) and current user is HR(3)/EXCOM(1)/HOD(2)
        $leave_requests = $leave_requests->map(function ($leaveRequest) use ($statusesGrouped, $rank, $loggedInEmployeeId, $loggedInRankStr, $approverIdToLabel) {
            $leaveRequest->from_date = Carbon::parse($leaveRequest->from_date)->format('d M');
            $leaveRequest->to_date = Carbon::parse($leaveRequest->to_date)->format('d M');
            $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);

            $leaveRequest->combinedLeave = EmployeeLeave::where('flag', $leaveRequest->id)
                ->join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                ->first();

            // Status Logic — use main leave status as source of truth
            $statuses = $statusesGrouped[$leaveRequest->id] ?? collect();
            $isPending = false;
            $isFullyApproved = false;

            if ($leaveRequest->status === 'Approved') {
                $leaveRequest->status_text = "Approved";
                $isFullyApproved = true;
            } elseif ($leaveRequest->status === 'Rejected') {
                $rejected = $statuses->firstWhere('status', 'Rejected');
                if ($rejected) {
                    $rejectedBy = $rank[$rejected->approver_rank] ?? $rejected->approver_rank;
                    $leaveRequest->status_text = "Rejected by {$rejectedBy}";
                } else {
                    $leaveRequest->status_text = "Rejected";
                }
            } else {
                // Still pending — show partial approval progress if any
                $lastApproved = $statuses->where('status', 'Approved')->last();
                if ($lastApproved) {
                    $approvedBy = $rank[$lastApproved->approver_rank] ?? $lastApproved->approver_rank;
                    $leaveRequest->status_text = "Approved by {$approvedBy}";
                } else {
                    $leaveRequest->status_text = "Pending";
                    $isPending = true;
                }
            }

            // Can approve: (1) applicant's reporting_to, or (2) GM leave and current user is HR/EXCOM/HOD, or (3) current user has a Pending row in the approval chain (e.g. HR EXCOM as reporting manager)
            $reportingToInt = (int)($leaveRequest->reporting_to ?? 0);
            $applicantRankStr = trim((string)($leaveRequest->applicant_rank ?? ''));
            $isReportingManager = $reportingToInt > 0 && $reportingToInt === (int)$loggedInEmployeeId;
            $isGMLeaveApprover = ($applicantRankStr === '8') && in_array($loggedInRankStr, ['1', '2', '3'], true);
            $hasCurrentUserPendingRow = $statuses->contains(function ($row) use ($loggedInEmployeeId) {
                return (int)($row->approver_id ?? 0) === (int)$loggedInEmployeeId && strtolower(trim((string)($row->status ?? ''))) === 'pending';
            });
            $leaveRequest->can_approve = (bool)(($isReportingManager || $isGMLeaveApprover || $hasCurrentUserPendingRow) && $isPending && !$isFullyApproved);

            // Handle combined leaves
            if ($leaveRequest->combinedLeave) {
                $leaveRequest->to_date = Carbon::parse($leaveRequest->combinedLeave->to_date)->format('d M');
                $leaveRequest->total_days += $leaveRequest->combinedLeave->total_days;
            }

            $leaveRequest->routes = route('leave.details', base64_encode($leaveRequest->id));
            return $leaveRequest;
        });

        return response()->json([
            'draw' => $request->get('draw', 1),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $leave_requests,
            'permission' => $permission,
        ]);
    }

    
    public function getLeaveChartData(Request $request)
    {
        $currentYear = $request->input('YearWiseLeaveHistory') ?: date('Y');
        $currentYear = (string)(int)$currentYear;
        if ((int)$currentYear < 2000 || (int)$currentYear > 2100) {
            $currentYear = date('Y');
        }

        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;

        // Get all months for the selected year in "M Y" format
        $months = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            return date('M Y', mktime(0, 0, 0, $month, 1, (int)$currentYear));
        });

        $startDate = $currentYear . '-01-01';
        $endDate = $currentYear . '-12-31';

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
        $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
        $hodDeptId = $loggedInEmployee->Dept_id ?? null;

        // Only HR department sees whole resort; other departments see only their data
        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('name', 'Human Resources')
            ->value('id');
        $isFromHRDepartment = $hrDeptId && $hodDeptId && (int) $hodDeptId === (int) $hrDeptId;
        $canViewWholeResort = $isFromHRDepartment;

        if ($canViewWholeResort) {
            // Whole resort: one row per leave, correct SUM(total_days); exclude own leave
            $leavesDataQuery = DB::table('employees_leaves as el')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->where('el.status', "Approved")
                ->where('el.resort_id', $this->resort->resort_id)
                ->whereBetween('el.from_date', [$startDate, $endDate])
                ->where('el.emp_id', '!=', $loggedInEmployeeId);
        } else {
            // Other departments: only their department's leaves (where user was approver)
            $leaveIds = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                ->where('el.status', "Approved")
                ->where('el.resort_id', $this->resort->resort_id)
                ->whereBetween('el.from_date', [$startDate, $endDate])
                ->where('el.emp_id', '!=', $loggedInEmployeeId)
                ->where('els.approver_id', $loggedInEmployeeId)
                ->when($hodDeptId, function ($q) use ($hodDeptId) {
                    $q->where('e.Dept_id', $hodDeptId);
                })
                ->distinct()
                ->pluck('el.id');

            $leavesDataQuery = DB::table('employees_leaves as el')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->where('el.status', "Approved")
                ->where('el.resort_id', $this->resort->resort_id)
                ->whereBetween('el.from_date', [$startDate, $endDate])
                ->where('el.emp_id', '!=', $loggedInEmployeeId);

            if ($leaveIds->isNotEmpty()) {
                $leavesDataQuery->whereIn('el.id', $leaveIds->toArray());
            } else {
                $leavesDataQuery->whereRaw('1 = 0');
            }
        }

        $leavesData = $leavesDataQuery
            ->groupBy(DB::raw("DATE_FORMAT(el.from_date, '%b %Y')"), 'lc.leave_type', 'lc.color')
            ->orderByRaw('DATE_FORMAT(el.from_date, "%Y-%m")')
            ->select(
                DB::raw("DATE_FORMAT(el.from_date, '%b %Y') as month_year"),
                'lc.leave_type',
                DB::raw('SUM(el.total_days) as total_days'),
                'lc.color as color'
            )->get();
        // dd($leavesData);

        // Extract unique leave types
        $leaveTypes = $leavesData->pluck('leave_type')->unique();

        // Create datasets for each leave type
        $datasets = $leaveTypes->map(function ($leaveType) use ($months, $leavesData) {
            $color = $leavesData->where('leave_type', $leaveType)->pluck('color')->first();

            $data = $months->map(function ($month) use ($leaveType, $leavesData) {
                $record = $leavesData->where('leave_type', $leaveType)->where('month_year', $month)->first();
                return $record ? $record->total_days : 0; // Default to 0 if no data exists
            });

            return [
                'label' => $leaveType,
                'data' => $data->toArray(),
                'backgroundColor' => $color,
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'borderRadius' => 10,
            ];
        });

        return response()->json([
            'labels' => $months->toArray(),
            'datasets' => $datasets->values(),
        ]);
    }

    public function sendBirthdayNotification(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $employee = Employee::with(['resortAdmin', 'position'])
            ->findOrFail($request->employee_id);

        if (!$employee->resortAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee admin information not found'
            ], 404);
        }

        $resortId = $this->resort->resort_id;
        $senderId = $this->resort->getEmployee->id;
        $birthdayPersonName = $employee->resortAdmin->first_name." ".$employee->resortAdmin->last_name;
        $birthdayPersonID = $employee->id;
        
        $notificationMessage = "🎉 Today is the birthday of: $birthdayPersonName! 🎂 Wish them a great day!";
        $notificationTitle = "Birthday Notification";
        
        // Get all employee IDs except the birthday person
        $receivers = Employee::where('resort_id', $resortId)
            ->where('id', '!=', $employee->id)
            ->pluck('id')
            ->toArray();

        // Send notifications
        foreach ($receivers as $receiverId) {
            event(new ResortNotificationEvent(Common::nofitication(
                    $resortId, 
                    10, // Use config with fallback
                    $notificationTitle, 
                    $notificationMessage,
                    $birthdayPersonID, // Assuming this is priority
                    $receiverId,
                    'Birthday', // More specific type
                )
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Birthday notifications sent to all employees!',
            'receivers_count' => count($receivers)
        ]);
    }
}