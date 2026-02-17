<?php

namespace App\Http\Controllers\Resorts\Payroll;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Earnings;
use App\Models\Deduction;
use App\Models\PayrollConfig;
use App\Models\PublicHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ParentAttendace;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\Payment;
use App\Models\ResortSiteSettings;
use App\Models\Payroll;
use App\Models\PayrollDeduction;
use App\Models\PayrollEmployees;
use App\Models\PayrollReview;
use App\Models\PayrollServiceCharge;
use App\Models\PayrollTimeAndAttendance;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;
use Common;

class PensionController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        $page_title ='Payroll Run';
        $resort_id = $this->resort->resort_id;
       
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Active'])->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();

        return view('resorts.payroll.pension.index',compact('page_title','positions','departments','employees'));
    }

    // public function getPensionData(Request $request)
    // {
    //     $resort_id = $this->resort->resort_id;
    
    //     // Adjust query to use proper functions for extracting month and year from the start_date field
    //     $query = DB::table('payroll_deductions as pd')
    //         ->join('payroll as p', 'p.id', '=', 'pd.payroll_id')
    //         ->join('employees as e', 'pd.employee_id', '=', 'e.id')
    //         ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
    //         ->join('resort_departments as rd', 'e.Dept_id', '=', 'rd.id')
    //         ->join('resort_positions as rp', 'e.Position_id', '=', 'rp.id')
    //         ->where('p.resort_id', $resort_id)
    //         ->select(
    //             'e.id as employee_id',
    //             'e.Emp_id as Emp_id',
    //             'e.Admin_Parent_id',
    //             'ra.first_name',
    //             'ra.last_name',
    //             'ra.profile_picture',
    //             'rd.name as department',
    //             'rd.code as department_code',
    //             'rp.position_title as position',
    //             'e.basic_salary',
    //             DB::raw('DATE_FORMAT(p.start_date, "%b") as month'), // Get abbreviated month name (e.g., "Jan")
    //             DB::raw('YEAR(p.start_date) as year'),
    //             'pd.pension as employee_pension',
    //             'pd.pension as employer_pension',
    //             'e.status'
    //         );

    //         if ($request->searchTerm) {
    //             $searchTerm = $request->searchTerm;
    //             $query->where(function ($q) use ($searchTerm) {
    //                 $q->where('ra.first_name', 'LIKE', "%{$searchTerm}%")
    //                   ->orWhere('ra.last_name', 'LIKE', "%{$searchTerm}%")
    //                   ->orWhere('rd.name', 'LIKE', "%{$searchTerm}%")
    //                   ->orWhere('rp.position_title', 'LIKE', "%{$searchTerm}%")
    //                   ->orWhere('e.basic_salary', 'LIKE', "%{$searchTerm}%")
    //                   ->orWhere('pd.pension', 'LIKE', "%{$searchTerm}%");
    //             });
    //         }
        
    //         if ($request->department) {
    //             $query->where('e.Dept_id', $request->department);
    //         }
    
    //         if ($request->position) {
    //             $query->orwhere('e.Position_id', $request->position);
    //         }

    //         $query->get();
    
    //     return datatables()->of($query)
    //        ->addColumn('name', function ($employee) {
    //             $profilePicture = Common::getResortUserPicture($employee->Admin_Parent_id);
    //             $fullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
    //             $fullName = trim($fullName) !== '' ? trim($fullName) : 'N/A';
                
    //             return '<div class="tableUser-block">
    //                         <div class="img-circle">
    //                             <img src="' . $profilePicture . '" alt="' . htmlspecialchars($fullName) . '" 
    //                                 onerror="this.src=\'/images/default-avatar.png\'">
    //                         </div>
    //                         <span>' . htmlspecialchars($fullName) . '</span>
    //                     </div>';
    //         })
    //         ->addColumn('department', function ($employee) {
    //             $departmentName =  $employee->department ?? 'N/A';
    //             $departmentCode =  $employee->department_code ?? 'N/A';
    //             return $departmentName.'<span class="badge badge-themeLight">'.$departmentCode.'</span>';
    //         })
    //         ->addColumn('position', function ($employee) {
    //             return $employee->position ?? 'N/A';
    //         })
    //         ->addColumn('basic_salary', function ($employee) {
    //             return $employee->basic_salary ? '$' . number_format($employee->basic_salary, 2) : 'N/A';
    //         })
    //         ->addColumn('time', function ($employee) {
    //             return $employee->month . " " . $employee->year ?? 'N/A';
    //         })
    //         ->addColumn('pension_percentage', function ($employee) {
    //             return isset($employee->contribution) ? $employee->contribution . '%' : '7%';
    //         })
    //         ->rawColumns(['name', 'department', 'position', 'basic_salary', 'time', 'pension_percentage'])
    //         ->make(true);
    // }
    public function getPensionData(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $query = DB::table('payroll_deductions as pd')
            ->join('payroll as p', 'p.id', '=', 'pd.payroll_id')
            ->join('employees as e', 'pd.employee_id', '=', 'e.id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('resort_departments as rd', 'e.Dept_id', '=', 'rd.id')
            ->join('resort_positions as rp', 'e.Position_id', '=', 'rp.id')
            ->leftJoin('employee_resignation as er', function($join) {
                $join->on('er.employee_id', '=', 'e.id')
                    ->where('er.status', 'approved'); // Only approved resignations
            })
            ->where('e.status','Active')
            ->where('p.resort_id', $resort_id)
            ->select(
                'e.id as employee_id',
                'e.Emp_id as Emp_id',
                'e.Admin_Parent_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rd.name as department',
                'rd.code as department_code',
                'rp.position_title as position',
                'e.basic_salary',
                DB::raw('DATE_FORMAT(p.start_date, "%b") as month'),
                DB::raw('YEAR(p.start_date) as year'),
                'pd.pension as employee_pension',
                'pd.pension as employer_pension',
                'pd.created_at',
                'e.status',
                'er.resignation_date',
                'er.last_working_day'
            );

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ra.first_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('ra.last_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('rd.name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('rp.position_title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('e.basic_salary', 'LIKE', "%{$searchTerm}%")
                ->orWhere('pd.pension', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->department) {
            $query->where('e.Dept_id', $request->department);
        }

        if ($request->position) {
            $query->where('e.Position_id', $request->position);
        }

        $totalQuery = clone $query;
        $totals = $totalQuery->select(
            DB::raw('SUM(pd.pension) as total_employee_pension'),
            DB::raw('SUM(pd.pension) as total_employer_pension') // Are these really the same?
        )->first();
        return datatables()->of($query)
        ->addColumn('name', function ($employee) {
                $profilePicture = Common::getResortUserPicture($employee->Admin_Parent_id);
                $fullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
                $fullName = trim($fullName) !== '' ? trim($fullName) : 'N/A';
                
                // Resignation indicator
                $resignedHtml = '';
                if ($employee->resignation_date) {
                    $resignDate = \Carbon\Carbon::parse($employee->resignation_date)->format('d M Y');
                    $lastDay = \Carbon\Carbon::parse($employee->last_working_day)->format('d M Y');

                    $resignedHtml = '
                        <span class="ttb-hover ttb-resigned ms-2" data-bs-toggle="tooltip"
                                        data-bs-placement="top" data-bs-title="Resigned" data-bs-date="'.$lastDay.'">
                            <i class="fa-regular fa-circle-exclamation"></i>
                            <span class="ttb-main">
                                <span class="ttb-inner">
                                    <h6><span>Resigned</span> - ' . $resignDate . '</h6>
                                    <p>Will be removed from the pension contributions starting ' . $lastDay . '</p>
                                </span>
                            </span>
                        </span>
                    ';
                }
                
                return '<div class="tableUser-block">
                            <div class="img-circle">
                                <img src="' . $profilePicture . '" alt="' . htmlspecialchars($fullName) . '" 
                                    onerror="this.src=\'/images/default-avatar.png\'">
                            </div>
                            <span>' . htmlspecialchars($fullName) . '</span>
                            ' . $resignedHtml . '
                        </div>';
            })
            ->addColumn('department', function ($employee) {
                $departmentName =  $employee->department ?? 'N/A';
                $departmentCode =  $employee->department_code ?? 'N/A';
                return $departmentName.'<span class="badge badge-themeLight">'.$departmentCode.'</span>';
            })
            ->addColumn('position', function ($employee) {
                return $employee->position ?? 'N/A';
            })
            ->addColumn('basic_salary', function ($employee) {
                return $employee->basic_salary ? '$' . number_format($employee->basic_salary, 2) : 'N/A';
            })
            ->addColumn('time', function ($employee) {
                return $employee->month . " " . $employee->year ?? 'N/A';
            })
            ->addColumn('pension_percentage', function ($employee) {
                return isset($employee->contribution) ? $employee->contribution . '%' : '7%';
            })
            ->addColumn('row_class', function ($employee) {
                return $employee->resignation_date ? 'danger-tr' : '';
            })
             ->with([
                'totals' => [
                    'employee_pension' => $totals->total_employee_pension ?? 0,
                    'employer_pension' => $totals->total_employer_pension ?? 0
                ]
            ])
            ->rawColumns(['name', 'department', 'position', 'basic_salary', 'time', 'pension_percentage'])
            ->make(true);
    }

    public function formerEmployees(Request $request)
    {
        $page_title = 'Former Employees';
        $resort_id = $this->resort->resort_id;
        $resort_id = $this->resort->resort_id;
       
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Resigned','Terminated','Inactive'])->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();

        if($request->ajax()) {
            $query = DB::table('payroll_deductions as pd')
            ->join('payroll as p', 'p.id', '=', 'pd.payroll_id')
            ->join('employees as e', 'pd.employee_id', '=', 'e.id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('resort_departments as rd', 'e.Dept_id', '=', 'rd.id')
            ->join('resort_positions as rp', 'e.Position_id', '=', 'rp.id')
            ->leftJoin('employee_resignation as er', function($join) {
                $join->on('er.employee_id', '=', 'e.id')
                    ->where('er.status', 'Approved');
            });    

        $query->where('p.resort_id', $resort_id)
            ->whereIn('e.status', ['Resigned', 'Terminated', 'Inactive'])
            ->select(
               'e.id as employee_id',
                'e.Emp_id as Emp_id',
                'e.Admin_Parent_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rd.name as department',
                'rd.code as department_code',
                'rp.position_title as position',
                'e.basic_salary',
                DB::raw('DATE_FORMAT(p.start_date, "%b") as month'),
                DB::raw('YEAR(p.start_date) as year'),
                'pd.pension as employee_pension',
                'pd.pension as employer_pension',
                'e.status',
                'er.resignation_date',
                'er.last_working_day'
            );

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ra.first_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('ra.last_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('rd.name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('rp.position_title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('e.basic_salary', 'LIKE', "%{$searchTerm}%")
                ->orWhere('pd.pension', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->department) {
            $query->where('e.Dept_id', $request->department);
        }

        if ($request->position) {
            $query->where('e.Position_id', $request->position);
        }

        $totalQuery = clone $query;
        $totals = $totalQuery->select(
            DB::raw('SUM(pd.pension) as total_employee_pension'),
            DB::raw('SUM(pd.pension) as total_employer_pension') // Are these really the same?
        )->first();

        return datatables()->of($query)
            ->addColumn('name', function ($employee) {
                $profilePicture = Common::getResortUserPicture($employee->Admin_Parent_id);
                $fullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
                $fullName = trim($fullName) !== '' ? trim($fullName) : 'N/A';
                
                // Resignation indicator
                $resignedHtml = '';
                if ($employee->resignation_date) {
                    $resignDate = \Carbon\Carbon::parse($employee->resignation_date)->format('d M Y');
                    $lastDay = \Carbon\Carbon::parse($employee->last_working_day)->format('d M Y');

                    $resignedHtml = '
                        <span class="ttb-hover ttb-resigned ms-2" data-bs-toggle="tooltip"
                                        data-bs-placement="top" data-bs-title="Resigned" data-bs-date="'.$lastDay.'">
                            <i class="fa-regular fa-circle-exclamation"></i>
                            <span class="ttb-main">
                                <span class="ttb-inner">
                                    <h6><span>Resigned</span> - ' . $resignDate . '</h6>
                                    <p>Will be removed from the pension contributions starting ' . $lastDay . '</p>
                                </span>
                            </span>
                        </span>
                    ';
                }
                
                return '<div class="tableUser-block">
                            <div class="img-circle">
                                <img src="' . $profilePicture . '" alt="' . htmlspecialchars($fullName) . '" 
                                    onerror="this.src=\'/images/default-avatar.png\'">
                            </div>
                            <span>' . htmlspecialchars($fullName) . '</span>
                            ' . $resignedHtml . '
                        </div>';
            })
            ->addColumn('department', function ($employee) {
                $departmentName =  $employee->department ?? 'N/A';
                $departmentCode =  $employee->department_code ?? 'N/A';
                return $departmentName.'<span class="badge badge-themeLight">'.$departmentCode.'</span>';
            })
            ->addColumn('position', function ($employee) {
                return $employee->position ?? 'N/A';
            })
            ->addColumn('basic_salary', function ($employee) {
                return $employee->basic_salary ? '$' . number_format($employee->basic_salary, 2) : 'N/A';
            })
            ->addColumn('time', function ($employee) {
                return $employee->month . " " . $employee->year ?? 'N/A';
            })
            ->addColumn('pension_percentage', function ($employee) {
                return isset($employee->contribution) ? $employee->contribution . '%' : '7%';
            })
            ->addColumn('row_class', function ($employee) {
                return $employee->resignation_date ? 'danger-tr' : '';
            })
            ->with([
                'totals' => [
                    'employee_pension' => $totals->total_employee_pension ?? 0,
                    'employer_pension' => $totals->total_employer_pension ?? 0
                ]
            ])
            ->rawColumns(['name', 'department', 'position', 'basic_salary', 'time', 'pension_percentage'])
            ->make(true);
        }                                  
        return view('resorts.payroll.pension.former-employees', compact('page_title', 'employees','positions', 'departments'));
    }
}