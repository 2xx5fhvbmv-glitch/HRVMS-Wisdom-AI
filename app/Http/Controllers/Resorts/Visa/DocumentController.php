<?php

namespace App\Http\Controllers\Resorts\Visa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\SelectedEmployeesExport;
use Illuminate\Support\Facades\Session;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\EmployeeLanguage;
use App\Models\ResortBenifitGrid;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperiance;
use App\Models\EmployeesDocument;
use App\Models\SOSTeamManagementModel;
use App\Models\SOSRolesAndPermission;
use App\Models\SOSTeamMemeberModel;
use App\Models\ResortBudgetCost;
use App\Services\EmployeeAllowanceService;
use App\Models\EmployeeAllowance;
use App\Models\FilemangementSystem;
use App\Models\EmployeeBankDetails;
use App\Events\ResortNotificationEvent;
use App\Models\Compliance;
use App\Models\ManningandbudgetingConfigfiles;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
use App\Models\VisaDocumentType;

class DocumentController extends Controller
{


     public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;

    }
    public function index()
    {

        $page_title ="Document Management";
        $documentTypes = VisaDocumentType::select('id', 'documentname')->get();

        $last_emp = Employee::orderBy('id', 'desc')->where('resort_id', $this->resort->resort_id)->first();
        $resort_prefix = $this->resort->resort->resort_prefix;
        
        $employee_id = $resort_prefix.'-'.$last_emp->id + 1;

        $nationalitys = config('settings.nationalities');
        $countries = config('settings.countries');
        $resort_id = $this->resort->resort_id;

        
        $resort_divisions    = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $departments         = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $positions           = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $sections            = ResortSection::where('resort_id',$resort_id)->where('status','active')->get();
        $payrollAllowance    = ResortBudgetCost::where('resort_id', $resort_id)->where('is_payroll_allowance', '1')->get();
        $nationalitys        = config('settings.nationalities');
        $countries           = config('settings.countries');

        return view('resorts.Visa.document.index',compact('page_title', 'documentTypes','nationalitys','countries','departments','positions',
        'sections','resort_divisions','payrollAllowance','employee_id'));
    }
    public function Xpatsync(Request $request)
    {
        $page_title ="Xpat Sync";
        return view('resorts.Visa.document.xpatsync',compact('page_title'));
    }

    public function FetchAithrowData(Request $request)
    {
        $url  = env('AI_URL');
        dd($url );
    }

    /*
     |--------------------------------------------------------------------------
     | Manual (no-AI) document-extraction endpoints
     |--------------------------------------------------------------------------
     | The original "Document Segregation" step (step 2 of the create-employee
     | wizard) POSTed every uploaded document to the external AI service
     | (env AI_URL / AI_extract_work_details_URL) to auto-fill fields. When that
     | service is unreachable or slow, the passport check returned status=false
     | and the wizard's Promise.all rejected — leaving the user stuck on step 2.
     |
     | These endpoints replace the AI calls. They never call the AI service:
     | they just validate the upload and return success-shaped JSON (the same
     | shape the AI returned when it found nothing), so the wizard always
     | advances. The user then types the passport / CV / education / experience
     | details by hand. Each one is intentionally tiny and side-effect free.
     */

    /**
     * Passport — replaces visa.passport.Checkexpiry (RenewalController@PassportExpiry).
     * The wizard resolves when response.status is truthy; blank date fields make
     * the UI show "status unclear, please check manually".
     */
    public function PassportExpiryManual(Request $request)
    {
        return response()->json([
            'status'     => true,
            'message'    => 'Passport uploaded. Please enter the passport details manually.',
            'expiryDate' => '',
            'issue_date' => '',
            'passportno' => '',
        ]);
    }

    /**
     * CV — replaces resort.visa.CheckCv (RenewalController@CheckCv).
     * Empty `data` => the wizard skips auto-fill (same as the AI "Details Not
     * Found" branch) and the user fills the personal fields manually.
     */
    public function CheckCvManual(Request $request)
    {
        return response()->json([
            'status'  => true,
            'message' => 'CV uploaded. Please enter the personal details manually.',
            'data'    => '',
        ]);
    }

    /**
     * Education — replaces resort.visa.Education (RenewalController@Education).
     */
    public function EducationManual(Request $request)
    {
        return response()->json([
            'status'  => true,
            'message' => 'Education document uploaded. Please enter the details manually.',
            'data'    => '',
        ]);
    }

    /**
     * Experience — replaces resort.visa.Experience (RenewalController@Experience).
     */
    public function ExperienceManual(Request $request)
    {
        return response()->json([
            'status'  => true,
            'message' => 'Experience document uploaded. Please enter the details manually.',
            'data'    => '',
        ]);
    }
    /**
     * Attach uploaded documents to an EXISTING employee's File Management folder.
     *
     * The document-management wizard no longer creates an employee. HR uploads a
     * passport + supporting documents; the passport number is read client-side
     * from the MRZ and posted here. We look up the employee that passport belongs
     * to and file every uploaded document into that employee's categorized File
     * Management folder (so it shows up under their folder, and in their employee
     * documents list). Nothing is inserted into resort_admins / employees.
     */
    public function CreateEmployee(Request $request)
    {
        $resortId = $this->resort->resort_id;

        // 1) Passport number (extracted from the MRZ on the client, or typed in).
        $passportNo = trim((string) $request->input('passport_numb'));
        if ($passportNo === '') {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'No passport number was detected from the uploaded passport. Please re-upload a clearer passport copy or enter the passport number manually.',
            ]);
        }

        // 2) Find the existing employee this passport belongs to (within resort).
        //    Compare case-insensitively and ignore spaces so "MA 123456" matches
        //    "MA123456".
        $needle = strtoupper(str_replace(' ', '', $passportNo));
        $employee = Employee::where('resort_id', $resortId)
            ->whereRaw('REPLACE(UPPER(COALESCE(passport_number, "")), " ", "") = ?', [$needle])
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'status'  => 'not_found',
                'message' => "No employee found with passport number {$passportNo}. Please verify the passport, or onboard the employee before filing their documents.",
            ]);
        }

        // 3) Collect the uploaded documents and their type labels.
        $files = $request->file('documents', []);
        if (empty($files)) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'No documents were received to save. Please re-upload the documents and try again.',
            ]);
        }
        $types = (array) $request->input('document_types', []);

        // 4) Resolve the employee's categorized File Management folder. Every
        //    active employee owns one (categorized-folder invariant); fall back to
        //    creating it if somehow missing.
        $fileManagement = FilemangementSystem::where('resort_id', $resortId)
            ->where('Folder_Name', $employee->Emp_id)
            ->where('Folder_Type', 'categorized')
            ->first();
        if (!$fileManagement) {
            $fileManagement = Common::createFolderByName($resortId, $employee->Emp_id, 'categorized');
        }
        if (!$fileManagement || empty($fileManagement->Folder_Name)) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => "Could not resolve the File Management folder for employee {$employee->Emp_id}. Please contact an administrator.",
            ]);
        }
        $folder_name = $fileManagement->Folder_Name;
        $userId      = Auth::guard('resort-admin')->user()->id;

        DB::beginTransaction();
        try {
            $saved = [];

            foreach ($files as $i => $file) {
                if (!$file) {
                    continue;
                }
                $title  = trim((string) ($types[$i] ?? '')) ?: ('Document ' . ($i + 1));
                $upload = Common::AWSEmployeeFileUpload($resortId, $file, $folder_name);

                if (($upload['status'] ?? false) === true) {
                    EmployeesDocument::create([
                        'employee_id'        => $employee->id,
                        'resort_id'          => $resortId,
                        'document_title'     => $title,
                        'document_path'      => $upload['path'],
                        'document_category'  => 'Visa',
                        'document_file_size' => $file->getSize(),
                        'created_by'         => $userId,
                        'modified_by'        => $userId,
                    ]);
                    $saved[] = $title;
                }
            }

            // Optional processed/whitened passport photo.
            if ($request->hasFile('photo')) {
                $photo   = $request->file('photo');
                $photoUp = Common::AWSEmployeeFileUpload($resortId, $photo, $folder_name);
                if (($photoUp['status'] ?? false) === true) {
                    EmployeesDocument::create([
                        'employee_id'        => $employee->id,
                        'resort_id'          => $resortId,
                        'document_title'     => 'Passport Photo',
                        'document_path'      => $photoUp['path'],
                        'document_category'  => 'Visa',
                        'document_file_size' => $photo->getSize(),
                        'created_by'         => $userId,
                        'modified_by'        => $userId,
                    ]);
                    $saved[] = 'Passport Photo';
                }
            }

            if (empty($saved)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'The documents could not be uploaded. Please try again.',
                ]);
            }

            DB::commit();

            $empName = optional($employee->resortAdmin)->full_name ?: $employee->Emp_id;

            return response()->json([
                'success'  => true,
                'status'   => 'success',
                'message'  => count($saved) . ' document(s) saved to ' . $empName . " (Emp ID {$employee->Emp_id}) in File Management.",
                'employee' => [
                    'id'     => $employee->id,
                    'emp_id' => $employee->Emp_id,
                    'name'   => $empName,
                    'folder' => $folder_name,
                ],
                'documents' => $saved,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Visa document attach failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Error saving documents: ' . $e->getMessage(),
            ]);
        }
    }
}
