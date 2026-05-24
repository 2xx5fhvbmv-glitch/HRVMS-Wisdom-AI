<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\OfflineInterview;
use App\Models\OfflineInterviewDocument;
use App\Models\Applicant_form_data;
use App\Models\ApplicantWiseStatus;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\Country;
use Auth;
use Carbon\Carbon;

class OfflineInterviewController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) return;
    }

    /* ============================================================
     * LIST + CREATE entry points
     * ============================================================ */

    public function index(Request $request)
    {
        $page_title = 'Offline Interview';

        // DataTable AJAX
        if ($request->ajax()) {
            $q = OfflineInterview::with(['applicant', 'department', 'position', 'createdEmployee.resortAdmin'])
                ->where('resort_id', $this->resort->resort_id)
                ->orderByDesc('id');

            if ($request->filled('status')) {
                $q->where('wizard_status', $request->status);
            }
            if ($request->filled('search')) {
                $term = trim($request->search);
                $q->whereHas('applicant', function ($qa) use ($term) {
                    $qa->where('first_name', 'like', "%{$term}%")
                       ->orWhere('last_name', 'like', "%{$term}%")
                       ->orWhere('email', 'like', "%{$term}%")
                       ->orWhere('passport_no', 'like', "%{$term}%");
                })->orWhere('position_title', 'like', "%{$term}%");
            }

            return datatables()->of($q)
                ->addColumn('candidate_name', function ($row) {
                    $a = $row->applicant;
                    if (!$a) return '—';
                    return e(trim($a->first_name . ' ' . $a->last_name)) ?: '—';
                })
                ->addColumn('passport', fn($row) => optional($row->applicant)->passport_no ?? '—')
                ->addColumn('email', fn($row) => optional($row->applicant)->email ?? '—')
                ->addColumn('position', fn($row) => $row->position_title
                    ?: (optional($row->position)->position_title ?? '—'))
                ->addColumn('department', fn($row) => optional($row->department)->name ?? '—')
                ->addColumn('status', function ($row) {
                    $badge = match ($row->wizard_status) {
                        'Selected'  => 'badge-themeSuccess',
                        'Rejected'  => 'badge-themeDanger',
                        'Withdrawn' => 'badge-themeGray',
                        'In Progress' => 'badge-themeSkyblue',
                        default     => 'badge-themeWarning',
                    };
                    return '<span class="badge ' . $badge . '">' . e($row->wizard_status) . '</span>';
                })
                ->addColumn('step', fn($row) => 'Step ' . (int) $row->current_step . ' / 5')
                ->addColumn('created_at_fmt', fn($row) => Carbon::parse($row->created_at)->format('d M Y h:i A'))
                ->addColumn('actions', function ($row) {
                    $id = base64_encode($row->id);
                    $continueLabel = in_array($row->wizard_status, ['Draft', 'In Progress'], true) ? 'Continue' : 'View';
                    return '
                        <div class="d-flex align-items-center gap-2">
                            <a href="' . route('offline-interview.create', ['id' => $id]) . '" class="btn-tableIcon btnIcon-skyblue" title="' . $continueLabel . '"><i class="fa-regular fa-eye"></i></a>
                            <a href="javascript:void(0)" class="btn-tableIcon btnIcon-danger offline-iv-delete" data-id="' . $row->id . '" title="Delete"><i class="fa-solid fa-trash"></i></a>
                        </div>';
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view('resorts.offline-interview.index', compact('page_title'));
    }

    /**
     * Wizard entry — either a new blank flow or a continued draft.
     * Pass ?id=base64(offline_interview_id) to resume.
     */
    public function create(Request $request)
    {
        $page_title = 'Offline Interview';
        $resort_id  = $this->resort->resort_id;

        $offlineInterview = null;
        if ($request->filled('id')) {
            $decoded = base64_decode($request->input('id'));
            if (is_numeric($decoded)) {
                $offlineInterview = OfflineInterview::with(['applicant', 'documents'])
                    ->where('resort_id', $resort_id)
                    ->find($decoded);
            }
        }

        $countries = Country::orderBy('name')->get();
        $divisions = ResortDivision::where('resort_id', $resort_id)->where('status', 'active')->get();
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status', 'active')->get();

        // Pre-populate the cascading dropdowns when continuing a draft so
        // Section / Position / Reporting render their already-chosen values
        // without waiting for the JS cascade to fire.
        $sections = collect();
        $positions = collect();
        $reportingCandidates = collect();
        if ($offlineInterview && $offlineInterview->department_id) {
            $sections = ResortSection::where('dept_id', $offlineInterview->department_id)
                ->where('resort_id', $resort_id)->where('status', 'active')->get();
            $positions = ResortPosition::where('dept_id', $offlineInterview->department_id)
                ->where('resort_id', $resort_id)->where('status', 'active')->get();
            $reportingCandidates = Employee::with('resortAdmin')
                ->where('resort_id', $resort_id)
                ->whereIn('rank', [1, 2])
                ->where('Dept_id', $offlineInterview->department_id)
                ->get()
                ->map(fn($e) => ['id' => $e->id, 'name' => optional($e->resortAdmin)->full_name]);
        }

        return view('resorts.offline-interview.create', compact(
            'page_title', 'resort_id', 'offlineInterview', 'countries', 'divisions',
            'departments', 'sections', 'positions', 'reportingCandidates'
        ));
    }

    /* ============================================================
     * Cascading dropdown helpers (unchanged — already wired in routes)
     * ============================================================ */

    public function getDepartmentsByDivision($division_id)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $departments = ResortDepartment::where('division_id', $division_id)
                ->where('resort_id', $resort_id)->where('status', 'active')->get();
            return response()->json(['success' => true, 'departments' => $departments]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch departments']);
        }
    }

    public function getSectionsByDept($dept_id)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $sections = ResortSection::where('dept_id', $dept_id)
                ->where('resort_id', $resort_id)->where('status', 'active')->get();
            return response()->json(['success' => true, 'sections' => $sections]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch sections']);
        }
    }

    public function getPositionByDept($dept_id)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $positions = ResortPosition::where('dept_id', $dept_id)
                ->where('resort_id', $resort_id)->where('status', 'active')->get();
            return response()->json(['success' => true, 'positions' => $positions]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch positions']);
        }
    }

    public function getReportingEmployess($dept_id)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $employees = Employee::with('resortAdmin')
                ->where('resort_id', $resort_id)
                ->where(function ($q) use ($dept_id) {
                    $q->whereIn('rank', [1, 2])  // EXCOM + HOD
                      ->where('Dept_id', $dept_id);
                })
                ->get()
                ->map(fn($e) => ['id' => $e->id, 'name' => optional($e->resortAdmin)->full_name]);
            return response()->json(['success' => true, 'employees' => $employees]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch employees']);
        }
    }

    /* ============================================================
     * Wizard — Step persistence + finalize
     * ============================================================ */

    /**
     * Step 1 — Hiring Requisition. Creates (or updates) the offline_interviews
     * shell with position / department / budget context. Must run before Step 2
     * (Applicant Info) — the candidate is being recruited *for* this position.
     */
    public function saveStep1(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $authId    = optional($this->resort)->id;

        $validated = $request->validate([
            'budgeted_or_out_of_budget' => 'nullable|in:Budgeted,Out of Budget',
            'division_id'               => 'nullable|exists:resort_divisions,id',
            'department_id'             => 'nullable|exists:resort_departments,id',
            'section_id'                => 'nullable|exists:resort_sections,id',
            'position_id'               => 'nullable|exists:resort_positions,id',
            'reporting_to'              => 'nullable|exists:employees,id',
            'position_title'            => 'nullable|string|max:191',
            'rank'                      => 'nullable|integer',
            'required_starting_date'    => 'nullable|date',
            'employee_type'             => 'nullable|in:Permanant,Casual/Agency,Trainee / Intern,Replacement,Temporary / Project',
            'service_provider_name'     => 'nullable|string|max:191',
            'salary'                    => 'nullable|string|max:50',
            'food'                      => 'nullable|string|max:50',
            'accommodation'             => 'nullable|string|max:50',
            'transportation'            => 'nullable|string|max:50',
            'budget_salary'             => 'nullable|numeric',
            'proposed_salary'           => 'nullable|numeric',
            'allowances'                => 'nullable|string|max:191',
            'medical'                   => 'nullable|string|max:50',
            'insurance'                 => 'nullable|string|max:50',
            'pension'                   => 'nullable|string|max:50',
            'service_charge'            => 'nullable|in:Yes,No',
            'uniform'                   => 'nullable|in:Yes,No',
            'benefit_accommodation'     => 'nullable|string|max:50',
            'recruitment_methods'       => 'nullable|array',
            'recruitment_methods.*'     => 'in:online_posting,recruiter,agency',
        ]);

        // Reuse existing offline interview (continuing draft) or start new.
        $oi = null;
        if ($request->filled('offline_interview_id')) {
            $oi = OfflineInterview::where('resort_id', $resort_id)
                ->find($request->input('offline_interview_id'));
        }
        if (!$oi) {
            $oi = new OfflineInterview();
            $oi->resort_id  = $resort_id;
            $oi->created_by = $authId;
        }

        $oi->fill($validated);
        $oi->wizard_status = $oi->wizard_status === 'Selected' ? $oi->wizard_status : 'In Progress';
        $oi->current_step  = max((int) $oi->current_step, 1);
        $oi->modified_by   = $authId;
        $oi->save();

        return response()->json([
            'success' => true,
            'message' => 'Hiring requisition saved.',
            'offline_interview_id' => $oi->id,
        ]);
    }

    /**
     * Step 2 — Applicant Information. Creates (or updates) the
     * applicant_form_data row and links it to the existing offline interview.
     */
    public function saveStep2(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $authId    = optional($this->resort)->id;

        $rules = [
            'offline_interview_id' => 'required|exists:offline_interviews,id',
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'gender'          => 'required|in:male,female,other',
            'dob'             => 'nullable|date',
            'mobile_number'   => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:191',
            'marital_status'  => 'nullable|string|max:30',
            'number_of_children' => 'nullable|integer|min:0',
            'address_line_one'=> 'nullable|string|max:255',
            'address_line_two'=> 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'country'         => 'nullable',
            'pin_code'        => 'nullable|string|max:20',
            'passport_no'     => 'nullable|string|max:50',
            'passport_expiry_date' => 'nullable|date',
            'curriculum_file' => 'nullable|file|mimes:pdf|max:10240',
            'passport_file'   => 'nullable|file|mimes:pdf|max:10240',
            'profile_picture' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'full_length_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ];
        $request->validate($rules);

        try {
            DB::beginTransaction();

            $oi = OfflineInterview::where('resort_id', $resort_id)
                ->findOrFail($request->input('offline_interview_id'));

            // Applicant row — update if previously linked, otherwise create new.
            if ($oi->applicant_form_data_id) {
                $applicant = Applicant_form_data::find($oi->applicant_form_data_id);
            }
            if (empty($applicant)) {
                $applicant = new Applicant_form_data();
                $applicant->resort_id = $resort_id;
                $applicant->Application_date = now();
            }

            foreach ([
                'first_name','last_name','gender','dob','mobile_number','email',
                'marital_status','number_of_children','address_line_one','address_line_two',
                'city','state','country','pin_code','passport_no','passport_expiry_date',
            ] as $field) {
                if ($request->filled($field) || $request->exists($field)) {
                    $applicant->{$field} = $request->input($field);
                }
            }

            // File uploads → per-applicant folder under the public disk.
            $storePath = $this->applicantStorageRoot($resort_id);
            foreach ([
                'curriculum_file' => 'curriculum_vitae',
                'passport_file'   => 'passport_file',
                'profile_picture' => 'profile_picture',
                'full_length_photo' => 'full_length_photo',
            ] as $field => $column) {
                if ($request->hasFile($field)) {
                    $applicant->{$column} = $this->storeUploadedFile($request->file($field), $storePath);
                }
            }
            $applicant->save();

            $oi->applicant_form_data_id = $applicant->id;
            $oi->current_step = max((int) $oi->current_step, 2);
            $oi->modified_by  = $authId;
            $oi->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Applicant details saved.',
                'offline_interview_id' => $oi->id,
                'applicant_form_data_id' => $applicant->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('OfflineInterview Step 2 failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 3 — Multi-file document uploads (CV + others).
     */
    public function saveStep3(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $request->validate([
            'offline_interview_id' => 'required|exists:offline_interviews,id',
            'documents'   => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,xlsx,xls|max:10240',
        ]);

        $oi = OfflineInterview::where('resort_id', $resort_id)->findOrFail($request->input('offline_interview_id'));

        $storePath = $this->applicantStorageRoot($resort_id) . '/oi-' . $oi->id;
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $stored = $this->storeUploadedFile($file, $storePath);
                OfflineInterviewDocument::create([
                    'offline_interview_id' => $oi->id,
                    'category' => 'documents',
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $stored,
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'uploaded_by' => optional($this->resort)->id,
                ]);
            }
        }

        $oi->current_step = max((int) $oi->current_step, 3);
        $oi->save();

        return response()->json([
            'success' => true,
            'message' => 'Documents uploaded.',
            'offline_interview_id' => $oi->id,
        ]);
    }

    /**
     * Step 4 — Interview rounds (HR / HOD / GM) + comments + scanned docs.
     */
    public function saveStep4(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $request->validate([
            'offline_interview_id' => 'required|exists:offline_interviews,id',
            'shortlisted_by_ai'    => 'nullable|boolean',
            'hr_shortlisted'       => 'nullable|boolean',
            'hr_round_status'      => 'nullable|in:Pending,Passed,Failed,Skipped',
            'hod_round_status'     => 'nullable|in:Pending,Passed,Failed,Skipped',
            'gm_round_status'      => 'nullable|in:Pending,Passed,Failed,Skipped',
            'round_comments'       => 'nullable|string|max:5000',
            'round_documents'      => 'nullable|array',
            'round_documents.*'    => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'round_category'       => 'nullable|in:hr_round,hod_round,gm_round',
        ]);

        $oi = OfflineInterview::where('resort_id', $resort_id)->findOrFail($request->input('offline_interview_id'));

        $oi->fill($request->only([
            'shortlisted_by_ai','hr_shortlisted','hr_round_status','hod_round_status',
            'gm_round_status','round_comments',
        ]));

        if ($request->hasFile('round_documents')) {
            $category = $request->input('round_category', 'hr_round');
            $storePath = $this->applicantStorageRoot($resort_id) . '/oi-' . $oi->id;
            foreach ($request->file('round_documents') as $file) {
                $stored = $this->storeUploadedFile($file, $storePath);
                OfflineInterviewDocument::create([
                    'offline_interview_id' => $oi->id,
                    'category' => $category,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $stored,
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'uploaded_by' => optional($this->resort)->id,
                ]);
            }
        }

        $oi->current_step = max((int) $oi->current_step, 4);
        $oi->modified_by  = optional($this->resort)->id;
        $oi->save();

        return response()->json([
            'success' => true,
            'message' => 'Interview rounds saved.',
            'offline_interview_id' => $oi->id,
        ]);
    }

    /**
     * Step 5 — Selection & Offer. When is_selected = Yes, also creates
     * the Employee + ResortAdmin (mirror of OnboardingController@convertApplicant)
     * so the candidate flows straight into People as status 'Onboarding'.
     */
    public function finalize(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $request->validate([
            'offline_interview_id' => 'required|exists:offline_interviews,id',
            'is_selected'          => 'required|in:Yes,No',
            'offer_letter'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'override_email'       => 'nullable|email|max:191',
        ]);

        $oi = OfflineInterview::with('applicant')
            ->where('resort_id', $resort_id)
            ->findOrFail($request->input('offline_interview_id'));

        try {
            DB::beginTransaction();

            if ($request->hasFile('offer_letter')) {
                $storePath = $this->applicantStorageRoot($resort_id) . '/oi-' . $oi->id;
                $stored = $this->storeUploadedFile($request->file('offer_letter'), $storePath);
                $oi->offer_letter_path = $stored;
                OfflineInterviewDocument::create([
                    'offline_interview_id' => $oi->id,
                    'category' => 'offer_letter',
                    'original_name' => $request->file('offer_letter')->getClientOriginalName(),
                    'file_path' => $stored,
                    'mime_type' => $request->file('offer_letter')->getClientMimeType(),
                    'size_bytes' => $request->file('offer_letter')->getSize(),
                    'uploaded_by' => optional($this->resort)->id,
                ]);
            }

            $oi->is_selected   = $request->input('is_selected');
            $oi->current_step  = 5;
            $oi->wizard_status = $oi->is_selected === 'Yes' ? 'Selected' : 'Rejected';
            $oi->modified_by   = optional($this->resort)->id;
            $oi->save();

            // Auto-create the Employee on Selected.
            if ($oi->is_selected === 'Yes' && !$oi->created_employee_id) {
                $result = $this->convertToEmployee($oi, $request->input('override_email'));
                if (!$result['success']) {
                    DB::rollBack();
                    return response()->json($result, $result['status'] ?? 422);
                }
                $oi->created_employee_id = $result['employee_id'];
                $oi->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $oi->is_selected === 'Yes'
                    ? 'Candidate selected — employee record created. They are now visible in People with status Onboarding.'
                    : 'Marked as not selected.',
                'offline_interview_id' => $oi->id,
                'employee_id' => $oi->created_employee_id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('OfflineInterview finalize failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to finalize: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $oi = OfflineInterview::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        if ($oi->created_employee_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete — an employee has already been created from this record.',
            ], 422);
        }
        $oi->delete();
        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }

    /* ============================================================
     * Helpers
     * ============================================================ */

    private function applicantStorageRoot(int $resortId): string
    {
        return $resortId . '/public/talent_acquisition/offline';
    }

    /**
     * Store an uploaded file under a unique name. Returns the relative path
     * within the 'public' disk so it can be re-served via Storage::url().
     */
    private function storeUploadedFile($file, string $directory): string
    {
        $name = 'oi_' . time() . '_' . substr(md5(uniqid('', true)), 0, 8)
              . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($directory, $name, 'public');
    }

    /**
     * Create Employee + ResortAdmin from a Selected offline_interview row.
     * Mirrors OnboardingController@convertApplicant — the candidate lands in
     * People as status 'Onboarding' for HR to activate later.
     */
    private function convertToEmployee(OfflineInterview $oi, ?string $overrideEmail = null): array
    {
        $resort_id = $oi->resort_id;
        $applicant = $oi->applicant;
        if (!$applicant) {
            return ['success' => false, 'message' => 'Applicant data is missing — complete Step 1 first.', 'status' => 422];
        }
        if (!$oi->department_id || !$oi->position_id) {
            return ['success' => false, 'message' => 'Hiring requisition is incomplete — complete Step 2 first.', 'status' => 422];
        }

        $applicantName = trim($applicant->first_name . ' ' . $applicant->last_name);
        $email = $overrideEmail !== null && $overrideEmail !== ''
            ? $overrideEmail
            : trim((string) $applicant->email);

        // Email-collision guard — resort_admins.email is UNIQUE.
        if ($email !== '') {
            $owner = ResortAdmin::where('email', $email)->first();
            if ($owner) {
                return [
                    'success' => false,
                    'code' => 'email_collision',
                    'status' => 422,
                    'applicant_name' => $applicantName,
                    'conflicting_email' => $email,
                    'owner_name' => trim($owner->first_name . ' ' . $owner->last_name),
                    'message' => 'Cannot create an employee record for ' . $applicantName
                        . ' — the email "' . $email . '" is already used by another account.',
                ];
            }
        }

        // Create ResortAdmin (login record).
        $resortAdmin = ResortAdmin::create([
            'resort_id'      => $resort_id,
            'first_name'     => $applicant->first_name,
            'last_name'      => $applicant->last_name,
            'email'          => $email !== '' ? $email : null,
            'personal_phone' => $applicant->mobile_number,
            'gender'         => $applicant->gender,
            'address_line_1' => $applicant->address_line_one,
            'address_line_2' => $applicant->address_line_two,
            'city'           => $applicant->city,
            'state'          => $applicant->state,
            'zip'            => $applicant->pin_code,
            'country'        => $applicant->country,
            'is_employee'    => 1,
            'status'         => 'active',
        ]);

        // Generate Emp_id.
        $resort_prefix = $this->resort->resort->resort_prefix ?? 'EMP';
        $last_emp = Employee::withTrashed()->where('resort_id', $resort_id)->orderByDesc('id')->first();
        $emp_id   = $resort_prefix . '-' . ($last_emp ? ($last_emp->id + 1) : 1);

        $dob = null;
        if (!empty($applicant->dob)) {
            try { $dob = Carbon::parse($applicant->dob)->format('Y-m-d'); }
            catch (\Throwable $e) { $dob = null; }
        }
        $marital = strtolower((string) $applicant->marital_status) === 'married' ? 'Married' : 'Single';
        $title   = strtolower((string) $applicant->gender) === 'female' ? 'Miss' : 'Mr';

        // Map employee_type to the employees enum.
        $employmentType = 'Full-Time';
        $vacType = strtolower((string) $oi->employee_type);
        if (str_contains($vacType, 'intern') || str_contains($vacType, 'trainee')) {
            $employmentType = 'Internship';
        } elseif (str_contains($vacType, 'replace')) {
            $employmentType = 'Contract';
        } elseif (str_contains($vacType, 'temporary')) {
            $employmentType = 'Temporary';
        } elseif (str_contains($vacType, 'casual')) {
            $employmentType = 'Casual';
        }

        $employee = Employee::create([
            'resort_id'             => $resort_id,
            'Emp_id'                => $emp_id,
            'applicant_id'          => $applicant->id,
            'Admin_Parent_id'       => $resortAdmin->id,
            'title'                 => $title,
            'Dept_id'               => $oi->department_id,
            'Section_id'            => $oi->section_id ?: null,
            'Position_id'           => $oi->position_id,
            'division_id'           => $oi->division_id ?: 0,
            'reporting_to'          => $oi->reporting_to ?: 0,
            'rank'                  => $oi->rank,
            'is_employee'           => 1,
            // Same pre-joining gate as convertApplicant — HR activates later.
            'status'                => 'Onboarding',
            'dob'                   => $dob,
            'marital_status'        => $marital,
            'joining_date'          => null,
            'employment_type'       => $employmentType,
            'passport_number'       => $applicant->passport_no,
            'basic_salary'          => $oi->proposed_salary ?: $oi->budget_salary,
            'basic_salary_currency' => 'USD',
            'present_address'       => trim(implode(', ', array_filter([
                $applicant->address_line_one, $applicant->address_line_two,
                $applicant->city, $applicant->state, $applicant->pin_code,
            ]))),
        ]);

        // Mark the applicant as Contract Accepted so the existing TA history
        // surfaces consistently.
        try {
            ApplicantWiseStatus::create([
                'Applicant_id' => $applicant->id,
                'As_ApprovedBy' => 3,
                'status' => 'Contract Accepted',
                'Comments' => 'Auto-created via Offline Interview wizard',
            ]);
        } catch (\Throwable $e) {
            // Non-fatal — the employee was created.
        }

        return ['success' => true, 'employee_id' => $employee->id];
    }
}
