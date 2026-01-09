<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee; // Ensure you import your model
use App\Models\ResortAdmin;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenifitGridChild;
use App\Models\EmployeeLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api'); // This will protect all methods in this controller
    }

    public function getEmployees($resortId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // $resortId       = $request->query('resort_id');
            $user           = Auth::guard('api')->user();
            $employee       = $user->GetEmployee;

            // Fetch rank and determine role
            $employeeRankPosition = Common::getEmployeeRankPosition($employee);
            $isHOD           = ($employeeRankPosition['rank'] === "HOD");
            $isHR            = ($employeeRankPosition['rank'] === "HR");

            // Fetch employees based on role
            $employees = collect(); // Initialize as empty collection

            if ($isHOD) {
                $subordinateIds     = Common::getSubordinates($employee->id);

                $employeesAndLeaveData = DB::table('employees as e')
                    ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                    ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                    ->where('e.resort_id', $resortId)
                    ->whereIn('e.id', $subordinateIds);

            } elseif ($isHR) {

                $employeesAndLeaveData = DB::table('employees as e')
                    ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                    ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                    ->where('e.resort_id', $resortId);

            }  else {
                $employeesAndLeaveData      = DB::table('employees as e')
                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                                            ->where('e.resort_id', $resortId);
            }
                $employeesAndLeaveData = $employeesAndLeaveData->where('e.deleted_at', null)
                ->select(
                    'ra.id',
                    'e.id as emp_id',
                    'e.resort_id',
                    'e.rank',
                    'e.joining_date',
                    'ra.first_name as employee_first_name',
                    'ra.last_name as employee_last_name',
                    'ra.profile_picture as employee_profile_picture',
                    'rp.position_title as position',
                    'rd.name as department',
                )->get();

                $leaveData = [];
                foreach ($employeesAndLeaveData as $key => $value) {

                    $profile = ResortAdmin::with([
                        'GetEmployee',
                      ])->find($value->id);

                    $value->employee_profile_picture = Common::getResortUserPicture($value->id);

                    $employee = Employee::where('Admin_Parent_id',$value->id)->first();
                    $emp_id = $employee->id;

                    $gender         = $profile->gender;
                    $religion       = $employee->religion;

                    if($religion == "1"){
                        $religion = "muslim";
                    }

                    // $emp_grade      = Common::getEmpGrade($value->rank);
                    $rank      = $value->rank;

                    if($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8){
                        $emp_grade = "1";
                    }
                    else if($rank == 4){
                        $emp_grade = "4";
                    }
                    else if($rank == 2){
                        $emp_grade = "2";
                    }
                    else if($rank == 5){
                        $emp_grade = "5";
                    }
                    else{
                        $emp_grade = "6";
                    }

                    $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                                                    ->where('resort_id', $resortId)
                                                    ->where('status', 'active') // Ensure to check for active status
                                                    ->first();

                        $leave_categories = ResortBenifitGridChild::select(
                            'resort_benefit_grid_child.*',
                                        'lc.leave_type',
                                        'lc.color',
                                        'lc.leave_category',
                                        'lc.combine_with_other','lc.id as leave_cat_id'
                                    )
                                    ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                                    ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                                    ->where('resort_benefit_grid_child.rank', $emp_grade)
                                    ->where('lc.resort_id', $resortId)
                                    ->where(function ($query) use ($religion,$gender) {
                                            $query->where('resort_benefit_grid_child.eligible_emp_type', $gender)
                                                ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                                            if ($religion == 'muslim') {
                                                $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                                            }

                                        })
                                    ->groupBy('resort_benefit_grid_child.id')
                                    ->get()
                                    ->map(function ($i) use ($emp_id) {
                                        $i->combine_with_other = isset($i->combine_with_other) ? $i->combine_with_other : 0;
                                        $i->leave_category = isset($i->leave_category) && $i->leave_category != "" ? $i->leave_category : 0;
                                        $i->ThisYearOfused_days = $this->employeeLeaveCounts($emp_id, $i->leave_cat_id);
                                        return $i;
                        });


                        $role                               = ucfirst(strtolower($value->rank ?? ''));
                        $rank                               = config('settings.Position_Rank');
                        $role                               = $rank[$role] ?? ''; // Fallback if rank is not in the config
                        $value->rank_type                   = $role;

                        $totalAllocatedDays        = collect($leave_categories)->sum('allocated_days');
                        $thisYearOfused_days       = collect($leave_categories)->sum('ThisYearOfused_days');

                        $leaveData[]               = [
                            'total_allocated_days' => $totalAllocatedDays,
                            'total_available_days' => $thisYearOfused_days,
                            'employee'             => $value, // Include employee data here
                        ];
                }

            $response['status']             =   true;
            $response['message']            =   'Employee list fetched successfully';
            $response['employee_data']       =   $leaveData;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function getEmployeesLeaves($emp_id)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user           = Auth::guard('api')->user();
            $resortId       = $user->resort_id;

            $profile        =   Employee::join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
                                    ->where('employees.id', $emp_id)
                                    ->select('employees.*','resort_admins.first_name','resort_admins.last_name','resort_admins.profile_picture')
                                    ->first();
            $gender         = $profile->gender;
            $religion       = $profile->religion;

            if($religion == "1"){
                $religion   = "muslim";
            }

            $rank           = $profile->rank;

                if($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8){
                    $emp_grade = "1";
                }
                else if($rank == 4){
                    $emp_grade = "4";
                }
                else if($rank == 2){
                    $emp_grade = "2";
                }
                else if($rank == 5){
                    $emp_grade = "5";
                }
                else{
                    $emp_grade = "6";
                }

                $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                ->where('resort_id', $resortId)
                ->where('status', 'active') // Ensure to check for active status
                ->first();

             $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                            'lc.leave_type',
                            'lc.color',
                            'lc.leave_category',
                            'lc.combine_with_other','lc.id as leave_cat_id'
                        )
                        ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                        ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                        ->where('resort_benefit_grid_child.rank', $emp_grade)
                        ->where('lc.resort_id', $resortId)
                        ->where(function ($query) use ($religion,$gender) {
                                $query->where('resort_benefit_grid_child.eligible_emp_type', $gender)
                                    ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                                if ($religion == 'muslim') {
                                    $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                                }
                            })
                        ->groupBy('resort_benefit_grid_child.id')
                        ->get()
                        ->map(function ($i) use ($emp_id) {
                            $i->combine_with_other = isset($i->combine_with_other) ? $i->combine_with_other : 0;
                            $i->leave_category = isset($i->leave_category) && $i->leave_category != "" ? $i->leave_category : 0;
                            $i->ThisYearOfused_days = $this->getLeaveCount($emp_id, $i->leave_cat_id);
                            return $i;
            });


                $totalAllocatedDays                     = collect($leave_categories)->sum('allocated_days');
                $thisYearOfused_days                    = collect($leave_categories)->sum('ThisYearOfused_days');
                $leaveData['leave_balances']            =  $leave_categories;
                $leaveData['total_allocated_days']      =  $totalAllocatedDays;
                $leaveData['this_year_of_used_days']    =  $thisYearOfused_days;
                $leaveData['first_name']                =  $profile->first_name;
                $leaveData['last_name']                 =  $profile->last_name;

                $response['status']                     =   true;
                $response['message']                    =   'Employee Leave Details';
                $response['leave_detail']               =   $leaveData;

                return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function getLeaveCount($emp_id, $leave_cat_id)
    {
        $employee = Employee::where('id',$emp_id)->first();
        $currentYearStart = Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d');

        $total_leave_days = EmployeeLeave::where('emp_id', $employee->id)
        ->where('leave_category_id', $leave_cat_id)
        ->where('status', 'Approved')
        ->where(function ($query) use ($currentYearStart, $currentMonthEnd) {
            $query->whereBetween('from_date', [$currentYearStart, $currentMonthEnd])
                  ->orWhereBetween('to_date', [$currentYearStart, $currentMonthEnd]);
        })
        ->sum('total_days');
        return  isset($total_leave_days) ? $total_leave_days:"0";
    }

    public function employeeLeaveCounts($emp_id, $leave_cat_id)
    {
        // $employee = Employee::where('Admin_Parent_id',$emp_id)->first();
        $currentYearStart = Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d');

        $total_leave_days = EmployeeLeave::where('emp_id', $emp_id)
        ->where('leave_category_id', $leave_cat_id)
        ->where('status', 'Approved')
        ->where(function ($query) use ($currentYearStart, $currentMonthEnd) {
            $query->whereBetween('from_date', [$currentYearStart, $currentMonthEnd])
                  ->orWhereBetween('to_date', [$currentYearStart, $currentMonthEnd]);
        })
        ->sum('total_days');
        return  isset($total_leave_days) ? $total_leave_days:"0";
    }

}

?>
