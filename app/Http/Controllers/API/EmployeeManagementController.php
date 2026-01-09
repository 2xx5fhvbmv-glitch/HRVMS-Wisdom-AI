<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Models\AssingAccommodation;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use Auth;
use DB;

class EmployeeManagementController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
            $this->reporting_to = $this->user->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function hrEmployeeOverview(Request $request)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
       
        try {
            $employees                                  =   Employee::select([
                                                                'employees.id as employee_id',
                                                                't1.id as Parentid',
                                                                't1.first_name',
                                                                't1.last_name',
                                                                't1.email',
                                                                't1.personal_phone',
                                                                't1.profile_picture',
                                                                't2.position_title',
                                                                'employees.Emp_id',
                                                                'employees.Dept_id',
                                                                'employees.Position_id'
                                                            ])
                                                            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                                                            ->leftJoin('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                                                            ->when($request->dept_id, function ($query, $dept_id) {
                                                                return $query->where('t3.id', $dept_id);
                                                            })  
                                                            ->where('t1.resort_id', $this->resort_id)->get()->map(function ($row) {
                                                                $row->profile_picture = Common::getResortUserPicture($row->Parentid);
                                                                return $row;
                                                            });
            if ($employees->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No employees found', 'hr_emp_over_data' => []], 200);
            }
                                                            
            return response()->json(['success' => true, 'message' => 'HR Employee Overview Data', 'hr_emp_over_data' => $employees], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodEmployeeOverview()
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
       
        try {
            $employees                                  =   Employee::select([
                                                                'employees.id as employee_id',
                                                                't1.id as Parentid',
                                                                't1.first_name',
                                                                't1.last_name',
                                                                't1.email',
                                                                't1.personal_phone',
                                                                't1.profile_picture',
                                                                't2.position_title',
                                                                'employees.Emp_id',
                                                            ])
                                                            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                                                            ->whereIn('employees.id', $this->underEmp_id)
                                                            ->where('t1.resort_id', $this->resort_id)->get()->map(function ($row) {
                                                                $row->profile_picture = Common::getResortUserPicture($row->Parentid);
                                                                return $row;
                                                            });
            if ($employees->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No employees found', 'hod_emp_over_data' => []], 200);
            }
            return response()->json(['success' => true, 'message' => 'Hod Employee Overview Data', 'hod_emp_over_data' => $employees], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hrOrganizationOverview()
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeCounts                                     =   Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                                        ->where('t1.resort_id', $this->resort_id)
                                                                        ->selectRaw("
                                                                            COUNT(*) AS total_employee_count,
                                                                            COUNT(CASE WHEN t1.gender = 'male' THEN 1 END) AS employee_male_count,
                                                                            COUNT(CASE WHEN t1.gender = 'female' THEN 1 END) AS employee_female_count
                                                                        ")->first();

            $localEmployees                                     =   Employee::where('nationality', 'Maldivian')->where('resort_id',$this->resort_id)->count();
            $expatEmployees                                     =   Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$this->resort_id)->count();
            $male_percentage                                    =   $employeeCounts->total_employee_count > 0 ? ($employeeCounts->employee_male_count / $employeeCounts->total_employee_count) * 100 : 0;
            $female_percentage                                  =   $employeeCounts->total_employee_count > 0 ? ($employeeCounts->employee_female_count / $employeeCounts->total_employee_count) * 100 : 0;

            $deptWiseData                                       =   DB::table('employees')
                                                                        ->join('resort_departments', 'employees.Dept_id', '=', 'resort_departments.id')
                                                                        ->join('resort_divisions', 'resort_departments.division_id', '=', 'resort_divisions.id')
                                                                        ->leftJoin('resort_sections',   'employees.Section_id', '=', 'resort_sections.id')
                                                                        ->select(
                                                                            'employees.resort_id',
                                                                            'resort_departments.name as department_name',
                                                                            'resort_divisions.name as division_name',
                                                                            DB::raw('COALESCE(resort_sections.name, "") as section_name'), // Convert NULL to ''
                                                                            DB::raw('COUNT(employees.emp_id) as employee_count')
                                                                        )
                                                                        ->where('employees.resort_id', 1)
                                                                        ->groupBy('employees.resort_id', 'resort_departments.name', 'resort_divisions.name','resort_sections.name')
                                                                        ->get();

            $employeesData = [
                'total_employee_count'                          =>  $employeeCounts->total_employee_count,
                // 'employee_male_count'                           =>  $employeeCounts->employee_male_count,
                // 'employee_female_count'                         =>  $employeeCounts->employee_female_count,
                'male_percentage'                               =>  $male_percentage,
                'female_percentage'                             =>  $female_percentage,
                'localEmployees'                                =>  $localEmployees,
                'expatEmployees'                                =>  $expatEmployees,
                'department_wise_emp'                           =>  $deptWiseData,
            ];

            return response()->json(['success' => true, 'message' => 'Hr Organization Overview Data', 'hr_org_data' => $employeesData], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    
    public function hodOrganizationOverview()
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeCounts                                     =   Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                                        ->where('t1.resort_id', $this->resort_id)
                                                                        ->whereIn('employees.id', $this->underEmp_id)
                                                                        ->selectRaw("
                                                                            COUNT(*) AS total_employee_count,
                                                                            COUNT(CASE WHEN t1.gender = 'male' THEN 1 END) AS employee_male_count,
                                                                            COUNT(CASE WHEN t1.gender = 'female' THEN 1 END) AS employee_female_count
                                                                        ")->first();

            $localEmployees                                     =   Employee::where('nationality', 'Maldivian')->where('resort_id',$this->resort_id)->whereIn('employees.id', $this->underEmp_id)->count();
            $expatEmployees                                     =   Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$this->resort_id)->whereIn('employees.id', $this->underEmp_id)->count();
            $male_percentage                                    =   $employeeCounts->total_employee_count > 0 ? ($employeeCounts->employee_male_count / $employeeCounts->total_employee_count) * 100 : 0;
            $female_percentage                                  =   $employeeCounts->total_employee_count > 0 ? ($employeeCounts->employee_female_count / $employeeCounts->total_employee_count) * 100 : 0;

            $sectionWiseData                                    =   DB::table('employees')
                                                                        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                                        ->join('resort_sections',   'employees.Section_id', '=', 'resort_sections.id')
                                                                        ->select(
                                                                            'employees.resort_id',
                                                                            'resort_sections.name as section_name',
                                                                            DB::raw('COUNT(employees.emp_id) as employee_count')
                                                                        )
                                                                        ->where('employees.resort_id', $this->resort_id)
                                                                        ->whereIn('employees.id', $this->underEmp_id)
                                                                        ->groupBy('employees.resort_id', 'resort_sections.name')
                                                                        ->get();
            $employeesData = [
                'total_employee_count'                          =>  $employeeCounts->total_employee_count,
                'male_percentage'                               =>  $male_percentage,
                'female_percentage'                             =>  $female_percentage,
                'localEmployees'                                =>  $localEmployees,
                'expatEmployees'                                =>  $expatEmployees,
                'section_wise_emp'                              =>  $sectionWiseData,
            ];

            return response()->json(['success' => true, 'message' => 'Hr Organization Overview Data', 'hod_org_data' => $employeesData], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    
    public function leaveRequestList()
    {


        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
           
            $startDate                                          =   Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate                                            =   Carbon::now()->endOfMonth()->format('Y-m-d');

            $leaveDetails                                       =   DB::table('employees_leaves as el')
                                                                        ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                                        ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                                                                        ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                                        ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                                                                        ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                                                                        ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                                        ->where('el.resort_id', $this->resort_id)
                                                                        ->whereBetween('el.from_date', [$startDate, $endDate]) // Filter by date range
                                                                        ->where('el.status', 'Pending')
                                                                        ->where('els.status', 'Pending');
            
            $leaveDetails                                       =   $leaveDetails->select(
                                                                        'el.id',
                                                                        'el.from_date',
                                                                        'el.to_date',
                                                                        'el.status',
                                                                        'e.Emp_id as employee_id',
                                                                        'ra.first_name as employee_first_name',
                                                                        'ra.last_name as employee_last_name',
                                                                        'ra.profile_picture as employee_profile_picture',
                                                                    )->groupBy('el.id')->get();
                                                                    
            return response()->json(['success' => true, 'message' => 'Employee Leave Request Data', 'employees_leave_data' => $leaveDetails], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    } 
}
