<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperiance;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use Validator;
use Auth;
use App\Imports\EmployeeImport;
use App\Jobs\ConsolidateBudgetImportJob;
use App\Helpers\Common;
use App\Exports\ExportEmployees;
use Maatwebsite\Excel\Facades\Excel;

use App\Jobs\ImportEmployeesJob;
class EmployeeController extends Controller
{

    public $globalUser='';
    public $currency = '';
    public $currencylogo = '';
    public function __construct()
    {
        $this->globalUser = Auth::guard('resort-admin')->user();
        if(!$this->globalUser) return;
        $this->currency = Common::GetResortCurrentCurrency();
        $this->currencylogo = Common::GetResortCurrencyLogo();

    }
    public function index(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        $page_title = 'Employee List';
        if ($request->ajax()) {

            $user = Auth::guard('resort-admin')->user();
            $employee = $user->GetEmployee;
            $Dept_id = $employee->Dept_id ?? null;

            // Eager-load the relations the DataTable renders so we don't N+1 per row.
            $query = Employee::where('employees.resort_id', $user->resort_id)
                ->where('employees.status', 'Active')
                ->with(['resortAdmin:id,first_name,middle_name,last_name,profile_picture', 'department:id,name', 'position:id,position_title']);

            // Standing department-based access rule:
            //   - Master / super / GM / HR / HR-dept HOD-EXCOM see all
            //   - Every other rank (incl. non-HR EXCOM rank 1) is scoped
            //     to their own department.
            // Earlier check `in_array($rank, [1,3])` let any rank-1 EXCOM
            // see every department, which violated the spec — e.g. an
            // Engineering EXCOM was seeing Accounting, F&B and HR rows.
            if (!Common::hasFullDataAccess()) {
                $query->where('employees.Dept_id', $Dept_id);
            }

            // Pass the QUERY (not a collection) so DataTables applies LIMIT/OFFSET server-side.
            return datatables()->eloquent($query)
            ->addColumn('name', function ($row) {
                $userprofile = Common::getResortUserPicture($row->Admin_Parent_id);
                $ra = $row->resortAdmin;
                $name = trim(($ra->first_name ?? '').' '.($ra->middle_name ?? '').' '.($ra->last_name ?? ''));
                return '
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="'.e($userprofile).'" alt="user">
                    </div>
                    <span class="userApplicants-btn">'.e($name).'</span>
                </div>';
            })
            ->addColumn('Department', fn($row) => $row->department ? e($row->department->name) : 'No Department Selected')
            ->addColumn('Position', fn($row) => $row->position ? e($row->position->position_title) : 'No Position Selected')
            ->addColumn('Rank', function ($row) {
                $Rank = config('settings.Position_Rank');
                return array_key_exists($row->rank, $Rank) ? $Rank[$row->rank] : '';
            })
            ->addColumn('Nation', fn($row) => e($row->nationality ?? ''))
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereHas('resortAdmin', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%")
                      ->orWhere('middle_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('Department', function ($query, $keyword) {
                $query->whereHas('department', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('Position', function ($query, $keyword) {
                $query->whereHas('position', fn($q) => $q->where('position_title', 'like', "%{$keyword}%"));
            })
            ->filterColumn('Nation', fn($q, $kw) => $q->where('nationality', 'like', "%{$kw}%"))
            ->rawColumns(['name'])
            ->make(true);
        }

        return view('resorts.employees.index', compact('page_title'));
    }

    public function getEmployeeNationalityData()
    {
        // CRITICAL: only count Active + Probationary employees. The
        // previous version counted ALL employees including Terminated,
        // Resigned, Retired, etc. — which made the Employee Type
        // doughnut disagree with every other count on the dashboard
        // (Compliance Tracking, Number of Local/Xpat, etc.) that
        // correctly filter by status.
        $current  = ['Active', 'Probationary'];
        $resortId = $this->globalUser->resort_id;

        $localEmployees = Employee::where('resort_id', $resortId)
            ->whereIn('status', $current)
            ->where('nationality', 'Maldivian')
            ->count();

        $expatEmployees = Employee::where('resort_id', $resortId)
            ->whereIn('status', $current)
            ->where('nationality', '!=', 'Maldivian')
            ->count();

        return response()->json([
            'local' => $localEmployees,
            'expat' => $expatEmployees,
        ]);
    }

    public function AddEmployee()
    {

        try {

            $page_title = 'Import Employee';
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

            $Department = ResortDepartment::where('resort_id',$resort_id)->where('status', 'active')->orderBy("id","desc")->get(['id', 'name']);
            return view('resorts.employees.ImportEmp',compact('page_title','Department'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'msg' => 'An error occurred while loading the page. Please try again later.'], 500);
        }
    }

    public function exportRelatedDepartment()
    {


        // $departments = ResortDepartment::with('positions')->get();
        // $data = [];

        // foreach ($departments as $department) {
        //     foreach ($department->positions as $position) {
        //         $data[$department->name] = [
        //             'Position' => $position->position_title,
        //         ];
        //     }
        // }

            return Excel::download(new ExportEmployees, 'ResortDepartmentAndPostionsList.xlsx');

    }
    public function ImportEmployee(Request $request)    
    {
        $validator = Validator::make($request->all(), [
            'Employeefile' => 'required|file|mimes:xls,xlsx', // Accept only Excel files up to 5MB
        ],[
            'Employeefile.required' => 'Please upload an Excel file.',
            'Employeefile.file' => 'The uploaded file must be a valid file.',
            'Employeefile.mimes' => 'The file must be an Excel sheet (xls or xlsx).',
        ]);
       
        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => $validator->errors()->first()], 422);
        }

        session()->forget('import_errors');

        if (!$request->hasFile('Employeefile')) {
            return response()->json(['success' => false, 'msg' => 'No file uploaded'], 422);
        }

        // Store file locally (storage/app/imports) with explicit disk specification
        $relativePath = $request->file('Employeefile')->store('imports', 'local');

        // Get full path
        $fullPath = storage_path('app/' . $relativePath);
        
        // Check if file was actually stored
        if (!file_exists($fullPath)) {
            return response()->json(['success' => false, 'msg' => 'Failed to store uploaded file'], 500);
        }

        try {
            // Import using full path
            Excel::import(new EmployeeImport(), $fullPath);

            $importErrors = session('import_errors');

            if (!empty($importErrors)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Some rows could not be imported',
                    'errors' => $importErrors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'msg' => "Employee Stored successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }



    // HOD UNDER EMPLOYEEList
    // public function HodEmployeelist()
    // {


    //         if ($request->ajax())
    //         {

    //             $user = Auth::guard('resort-admin')->user();

    //             $position_id = $user->GetEmployee->Position_id;
    //             $Dept_id = $user->GetEmployee->Dept_id;


    //             $employees = Employee::where('resort_id', $user->resort_id)
    //                 ->where('position_id', $position_id)
    //                 ->where('dept_id', $Dept_id)
    //                 ->where('resort_id', $user->resort_id)
    //                 ->where('rank',"=",'others')
    //                 ->get();


    //                 return datatables()->of($employees)
    //                 ->addColumn('name', function ($row)
    //                 {
    //                     $userprofile = url('resorts_assets/images/'.$row->profile_photo);
    //                     return '<img src="' . $userprofile . '" alt="user" class="profile-image"> ' . ucfirst($row->first_name . ' ' . $row->middle_name . ' ' . $row->last_name);
    //                 })
    //                 ->editColumn('Department', function ($row)
    //                 {
    //                     return $row->department ? $row->department->name : 'No Department Selected';
    //                 })
    //                 ->editColumn('Position', function ($row)
    //                 {
    //                     return $row->position ? $row->position->position_title : 'No Position Selected';
    //                 })
    //                 ->editColumn('Rank', function ($row)
    //                 {
    //                     return $row->rank;
    //                 })
    //                 ->editColumn('Nation', function ($row)
    //                 {
    //                     return $row->nationality;
    //                 })
    //                 ->rawColumns(['name', 'Department', 'Position', 'Rank', 'Nation']) // Added Nation to rawColumns
    //                 ->make(true);

    //         }
    //         return view('resorts.employees.hodEmployeeindex');

    // }



}
