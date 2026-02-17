<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\Employee;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;
use App\Models\Vacancies;
use App\Models\LearningProgram;
use App\Models\Applicant_form_data;
use App\Models\ChildFileManagement;
use App\Models\EmployeeLeave;
use App\Models\GrivanceSubmissionModel;
use App\Models\PublicHoliday;
use App\Models\Shopkeeper;
use App\Models\ResortAdmin;
use Illuminate\Support\Facades\Route;



class MasterSearchController extends Controller
{
    public $globalUser='';
    public function __construct()
    {
        $this->globalUser = Auth::guard('resort-admin')->user();
        if(!$this->globalUser) return;
    }

    public function index(Request $request){

        $search = $request->input('search_term');
        $getEmployee = [];
        $getVacancy = [];
        $getDocuments = [];
        $getAnnouncements = [];
        $getApplicants = [];
        $getDepartments = [];
        $getPositions = [];
        $getLearningPrograms = [];
        $getHolidays = [];
        $getEmployeeLeave = [];
        $getShopkeeper = [];

        if(!empty($search)){
            $searchValues = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

             $searchLike = '%' . $search . '%';
                $resort_id = $this->globalUser->resort_id;
                
                // Search employees
                $getEmployee = Employee::with(['resortAdmin', 'position', 'department'])
                    ->where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->orWhereHas('resortAdmin', function($q) use ($search_key) {
                                $q->where('first_name', 'LIKE', "%{$search_key}%")
                                ->orWhere('middle_name', 'LIKE', "%{$search_key}%")
                                ->orWhere('last_name', 'LIKE', "%{$search_key}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search_key}%"])
                                ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search_key}%"])
                                ->orWhere('email', 'LIKE', "%{$search_key}%")
                                ->orWhere('personal_phone', 'LIKE', "%{$search_key}%");
                            })
                            ->orWhere('Emp_id', 'LIKE', "%{$search_key}%");
                        }
                    })->get();
                    
                // Search vacancies
                $getVacancy = Vacancies::with(['Getposition', 'Getdepartment'])
                    ->where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->orWhereHas('Getposition', function($q) use ($search_key) {
                                $q->where('position_title', 'LIKE', "%{$search_key}%");
                            })
                            ->orWhereHas('Getdepartment', function($q) use ($search_key) {
                                $q->where('name', 'LIKE', "%{$search_key}%");
                            });
                        }
                    })
                    ->get();


                // // Search announcements
                $getAnnouncements = Announcement::where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->orWhere('title', 'LIKE', "%{$search_key}%")
                                  ->orWhere('message', 'LIKE', "%{$search_key}%");
                        }   
                    })
                    ->get();
                    
                // // Search departments
                $getDepartments = ResortDepartment::with(['division'])
                    ->where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->where('name', 'LIKE', "%{$search_key}%")
                                ->orWhere('code', 'LIKE', "%{$search_key}%")
                                ->orWhere('short_name', 'LIKE', "%{$search_key}%");
                        }
                    })
                    ->get();
                // // Search positions
                $getPositions = ResortPosition::where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->where('position_title', 'LIKE', "%{$search_key}%")
                                ->orWhere('code', 'LIKE', "%{$search_key}%")
                                ->orWhere('short_title', 'LIKE', "%{$search_key}%");
                        }
                    })
                    ->get();

                // // Search learning programs
                $getLearningPrograms = LearningProgram::where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                        $query->where('name', 'LIKE', "%{$search_key}%")
                            ->orWhere('description', 'LIKE', "%{$search_key}%")
                            ->orWhere('objectives', 'LIKE', "%{$search_key}%")
                            ->orWhere('frequency', 'LIKE', "%{$search_key}%")
                            ->orWhere('prior_qualification', 'LIKE', "%{$search_key}%");
                        }
                    })
                    ->get();
                

                // // Search documents
                $getDocuments = ChildFileManagement::where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->where('File_Name', 'LIKE', "%{$search_key}%")
                                ->orWhere('File_Type', 'LIKE', "%{$search_key}%");
                        }
                    })
                    ->get();


                // // Search applicants
                $getApplicants = Applicant_form_data::where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->where('first_name', 'LIKE', "%{$search_key}%")
                                ->orWhere('last_name', 'LIKE', "%{$search_key}%")
                                ->orWhere('mobile_number', 'LIKE', "%{$search_key}%")
                                ->orWhere('country', 'LIKE', "%{$search_key}%")
                                ->orWhere('email','LIKE',"%{$search_key}%");
                        }
                    })                    
                    ->get();

                // // Search holidays
                $getHolidays = PublicHoliday::where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                           $query->where('name', 'LIKE', "%{$search_key}%")
                            ->orWhere('description', 'LIKE', "%{$search_key}%");
                        }
                    })
                    ->get();

                $getEmployeeLeave = EmployeeLeave::with(['LeaveCategory'])->where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->whereHas('LeaveCategory',function($q) use ($search_key) {
                                $q->where('leave_type', 'LIKE', "%{$search_key}%");
                            })
                            ->where('reason', 'LIKE', "%{$search_key}%");
                        }
                    })
                    ->get();
                
                $getShopkeeper = Shopkeeper::where('resort_id', $resort_id)
                    ->where(function($query) use ($searchValues) {
                        foreach($searchValues as $search_key) {
                            $query->where('name','LIKE',"%{$search_key}%")
                            ->orWhere('email','LIKE',"%{$search_key}%");
                        }
                    })
                    ->get();
            }
        
        $html = view('resorts.search.index', compact('search','getEmployee','getVacancy','getAnnouncements','getPositions','getDepartments',
                            'getLearningPrograms','getDocuments','getApplicants','getHolidays','getEmployeeLeave','getShopkeeper'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }


}
