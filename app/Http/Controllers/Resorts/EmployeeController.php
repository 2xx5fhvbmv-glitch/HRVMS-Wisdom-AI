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
        $this->currency = Common::GetResortCurrentCurrency();
        $this->currencylogo = Common::GetResortCurrencyLogo();

    }
    public function index(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        $page_title = 'Employee List';
        if ($request->ajax()) {

            $user = Auth::guard('resort-admin')->user();
            $position_id = $user->GetEmployee->Position_id;
            $Dept_id = $user->GetEmployee->Dept_id;

            $employees = Employee::where('resort_id', $user->resort_id)
            ->with('resortAdmin')
            // ->where('position_id', $position_id)
            ->where('dept_id', $Dept_id)
            ->where('resort_id', $user->resort_id)
            ->get();

            return datatables()->of($employees)
            ->addColumn('name', function ($row) {
                $userprofile = Common::getResortUserPicture($row->Admin_Parent_id);

                return '<img style="width:50px;height:50px" src="' . $userprofile . '" alt="user" class="profile-image">'
                    . $row->resortAdmin->first_name . ' '
                    . $row->resortAdmin->middle_name . ' '
                    . $row->resortAdmin->last_name;
            })
            ->editColumn('Department', function ($row) {
                return $row->department ? $row->department->name : 'No Department Selected';
            })
            ->editColumn('Position', function ($row) {
                return $row->position ? $row->position->position_title : 'No Position Selected';
            })
            ->editColumn('Rank', function ($row) {
                $Rank = config( 'settings.Position_Rank');
                $AvilableRank = array_key_exists($row->rank, $Rank) ? $Rank[$row->rank] : '';
                return $AvilableRank;
            })
            ->editColumn('Nation', function ($row) {
                return $row->nationality;
            })
            ->rawColumns(['name', 'Department', 'Position', 'Rank', 'Nation']) // Added Nation to rawColumns
            ->make(true);
        }

        return view('resorts.employees.index', compact('page_title'));
    }

    public function getEmployeeNationalityData()
    {
        // Count employees where nationality is Maldivian (local)
        $localEmployees = Employee::where('resort_id',  $this->globalUser->resort_id)->where('nationality', 'Maldivian')->count();

        // Count employees where nationality is not Maldivian (expat)
        $expatEmployees = Employee::where('resort_id',  $this->globalUser->resort_id)->where('nationality', '!=', 'Maldivian')->count();

        // Return the counts as JSON
        return response()->json([
            'local' => $localEmployees,
            'expat' => $expatEmployees
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
