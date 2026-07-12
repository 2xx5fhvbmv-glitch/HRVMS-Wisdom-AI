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

        // ── Step 1: vacancy picker ───────────────────────────────────
        // Step 1 used to be a giant Hiring Requisition form. It's now a
        // table of the resort's currently posted vacancies — mirrors the
        // /talent-acquisition/fresh-applicant filters exactly so HR sees
        // the same shortlist on both pages:
        //   - has a t_anotification_children row Approved_By the TA final
        //     approver AND status='ForwardedToNext'
        //   - has an application_links row
        // Picking a row hydrates department / position / division / salary
        // / etc. on the offline interview shell, so the user goes straight
        // to Step 2 without re-typing data that already lives on the vacancy.
        // Filled-slot count per vacancy — UNION of two sources, max() taken
        // in the outer select so neither path can under-count. Mirrors
        // the same logic used by /resort/people/employees/create's
        // vacancy picker so the two pages agree on what's "still open":
        //   1. employees.vacancy_id — exact, set by the new-hire form
        //      after the 2026_06_01 migration. Schema-guarded for
        //      environments that haven't applied it yet.
        //   2. applicant_wise_statuses.status='Contract Accepted' on
        //      applicant_form_data.Parent_v_id — the TA-flow definition
        //      of "hired", works on every environment.
        $hasVacancyIdCol = \Schema::hasColumn('employees', 'vacancy_id');
        if ($hasVacancyIdCol) {
            $empFilled = \DB::table('employees')
                ->select('vacancy_id', \DB::raw('COUNT(*) as filled'))
                ->where('resort_id', $resort_id)
                ->whereNotIn('status', ['Terminated', 'Inactive'])
                ->whereNotNull('vacancy_id')
                ->groupBy('vacancy_id');
            $taFilled = \DB::table('applicant_wise_statuses as aws')
                ->join('applicant_form_data as afd', 'afd.id', '=', 'aws.Applicant_id')
                ->where('afd.resort_id', $resort_id)
                ->where('aws.status', 'Contract Accepted')
                ->groupBy('afd.Parent_v_id')
                ->select('afd.Parent_v_id as vacancy_id', \DB::raw('COUNT(DISTINCT aws.Applicant_id) as filled'));
            $filledByVacancy = $empFilled->unionAll($taFilled);
        } else {
            // No vacancy_id column → TA-flow only.
            $filledByVacancy = \DB::table('applicant_wise_statuses as aws')
                ->join('applicant_form_data as afd', 'afd.id', '=', 'aws.Applicant_id')
                ->where('afd.resort_id', $resort_id)
                ->where('aws.status', 'Contract Accepted')
                ->groupBy('afd.Parent_v_id')
                ->select('afd.Parent_v_id as vacancy_id', \DB::raw('COUNT(DISTINCT aws.Applicant_id) as filled'));
        }

        $vacancies = \DB::table('vacancies as v')
            ->join('resort_positions as p', 'p.id', '=', 'v.position')
            ->join('resort_departments as d', 'd.id', '=', 'v.department')
            ->join('t_anotification_parents as tap', 'tap.V_id', '=', 'v.id')
            ->join('t_anotification_children as tac', 'tac.Parent_ta_id', '=', 'tap.id')
            ->join('application_links as al', 'al.ta_child_id', '=', 'tac.id')
            ->leftJoin('applicant_form_data as a', function ($join) {
                // Don't count applicants whose LATEST status is Rejected
                // (matches fresh-applicant's leftJoin pattern).
                $join->on('a.Parent_v_id', '=', 'v.id')
                    ->whereNotExists(function ($q) {
                        $q->select(\DB::raw(1))
                            ->from('applicant_wise_statuses as check_aws')
                            ->whereRaw('check_aws.Applicant_id = a.id')
                            ->whereRaw('check_aws.id = (SELECT MAX(id) FROM applicant_wise_statuses WHERE Applicant_id = a.id)')
                            ->where('check_aws.status', 'Rejected');
                    });
            })
            ->leftJoinSub($filledByVacancy, 'fb', function ($j) {
                $j->on('fb.vacancy_id', '=', 'v.id');
            })
            ->where('v.Resort_id', $resort_id)
            ->where('tac.Approved_By', \App\Helpers\Common::TaFinalApproval($resort_id))
            ->where('tac.status', 'ForwardedToNext')
            ->groupBy('v.id', 'p.position_title', 'd.name', 'd.code', 'v.Total_position_required')
            ->select(
                'v.id as vacancy_id',
                'p.position_title',
                'd.name as department_name',
                'd.code as department_code',
                'v.Total_position_required as no_of_positions',
                \DB::raw('COALESCE(MAX(fb.filled), 0) as filled_count'),
                \DB::raw('COUNT(DISTINCT a.id) as application_count'),
                \DB::raw('MAX(a.Application_date) as application_date'),
                \DB::raw('MAX(al.link_Expiry_date) as link_expiry_date')
            )
            ->orderByDesc('v.id')
            ->get()
            ->map(function ($v) {
                $v->application_date_label = $v->application_date
                    ? \Carbon\Carbon::parse($v->application_date)->format('d M Y') : '—';
                $v->expiry_date_label = $v->link_expiry_date
                    ? \Carbon\Carbon::parse($v->link_expiry_date)->format('d M Y') : '—';
                $v->remaining_slots = max(0, (int) $v->no_of_positions - (int) $v->filled_count);
                return $v;
            })
            // Hide fully-filled vacancies — mirrors the employee-create
            // page's auto-hide. New hires must tie to an UNFILLED slot.
            ->filter(fn($v) => $v->remaining_slots > 0)
            ->values();

        // Selected vacancy details for the preview card when continuing a
        // draft — pulls position + department names so the preview can be
        // rendered without an AJAX round-trip.
        $selectedVacancy = null;
        if ($offlineInterview && $offlineInterview->position_id) {
            $selectedVacancy = \App\Models\ResortPosition::with('department')->find($offlineInterview->position_id);
        }

        return view('resorts.offline-interview.create', compact(
            'page_title', 'resort_id', 'offlineInterview', 'countries',
            'vacancies', 'selectedVacancy'
        ));
    }

    /**
     * Used by the Step 1 vacancy picker — the JS calls this when the user
     * clicks "Select" on a vacancy row, to render the details card before
     * they commit. Returns the full vacancy snapshot so the same payload
     * can be used by saveStep1 to hydrate the offline_interviews shell.
     */
    public function getVacancyDetails($vacancyId)
    {
        $resort_id = $this->resort->resort_id;
        $v = \DB::table('vacancies as v')
            ->join('resort_positions as p', 'p.id', '=', 'v.position')
            ->join('resort_departments as d', 'd.id', '=', 'v.department')
            ->leftJoin('resort_sections as s', 's.id', '=', 'v.section')
            ->leftJoin('resort_divisions as dv', 'dv.id', '=', 'v.division')
            ->leftJoin('employees as emp', 'emp.id', '=', 'v.reporting_to')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'emp.Admin_Parent_id')
            ->where('v.Resort_id', $resort_id)
            ->where('v.id', $vacancyId)
            ->select(
                'v.*',
                'p.position_title',
                'p.Rank as position_rank',
                'd.name as department_name',
                's.name as section_name',
                'dv.name as division_name',
                \DB::raw("CONCAT(COALESCE(ra.first_name,''), ' ', COALESCE(ra.last_name,'')) as reporting_to_name")
            )
            ->first();

        if (!$v) {
            return response()->json(['success' => false, 'message' => 'Vacancy not found.'], 404);
        }

        return response()->json(['success' => true, 'vacancy' => $v]);
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

        $request->validate([
            'vacancy_id' => 'required|exists:vacancies,id',
        ]);

        // Pull the vacancy + the position's title so we can copy the whole
        // requisition snapshot onto the offline interview shell.
        $vacancy = \DB::table('vacancies as v')
            ->join('resort_positions as p', 'p.id', '=', 'v.position')
            ->where('v.Resort_id', $resort_id)
            ->where('v.id', $request->input('vacancy_id'))
            ->select('v.*', 'p.position_title')
            ->first();

        if (!$vacancy) {
            return response()->json(['success' => false, 'message' => 'Vacancy not found in this resort.'], 404);
        }

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

        // Map vacancy columns → offline_interviews columns. The legacy
        // requisition form fields are kept on the OI row so Step 4/5/PDF
        // logic doesn't have to special-case "from-vacancy" rows.
        $oi->budgeted_or_out_of_budget = $vacancy->budgeted ?: null;
        $oi->division_id            = $vacancy->division ?: null;
        $oi->department_id          = $vacancy->department ?: null;
        $oi->section_id             = $vacancy->section ?: null;
        $oi->position_id            = $vacancy->position ?: null;
        $oi->reporting_to           = $vacancy->reporting_to ?: null;
        $oi->position_title         = $vacancy->position_title ?: null;
        $oi->rank                   = $vacancy->rank ?: null;
        $oi->required_starting_date = $vacancy->required_starting_date ?: null;
        $oi->employee_type          = $vacancy->employee_type ?: null;
        $oi->service_provider_name  = $vacancy->service_provider_name ?: null;
        $oi->salary                 = $vacancy->salary ?: null;
        $oi->food                   = $vacancy->food ?: null;
        $oi->accommodation          = $vacancy->accomodation ?: null; // vacancies col is "accomodation"
        $oi->transportation         = $vacancy->transportation ?: null;
        $oi->budget_salary          = $vacancy->budgeted_salary ?: null;
        $oi->proposed_salary        = $vacancy->propsed_salary ?: null; // vacancies col is "propsed_salary"
        $oi->allowances             = $vacancy->allowance ?: null;
        $oi->medical                = $vacancy->medical ?: null;
        $oi->insurance              = $vacancy->insurance ?: null;
        $oi->pension                = $vacancy->pension ?: null;
        $oi->service_charge         = $vacancy->service_charge ?: null;
        $oi->uniform                = $vacancy->uniform ?: null;
        $oi->benefit_accommodation  = $vacancy->budgeted_accomodation ?: null;
        $oi->recruitment_methods    = $vacancy->recruitment ? (array) explode(',', $vacancy->recruitment) : null;

        $oi->wizard_status = $oi->wizard_status === 'Selected' ? $oi->wizard_status : 'In Progress';
        $oi->current_step  = max((int) $oi->current_step, 1);
        $oi->modified_by   = $authId;
        $oi->save();

        return response()->json([
            'success' => true,
            'message' => 'Vacancy selected. Proceed to Applicant Information.',
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
            // Now accepts multiple files (offer letter + contract + …).
            'offer_letter'         => 'nullable|array',
            'offer_letter.*'       => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'override_email'       => 'nullable|email|max:191',
        ]);

        $oi = OfflineInterview::with('applicant')
            ->where('resort_id', $resort_id)
            ->findOrFail($request->input('offline_interview_id'));

        // Collect newly-uploaded files so we can email them after the
        // transaction commits. Each entry: [path, name, mime].
        $newlyUploaded = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('offer_letter')) {
                $storePath = $this->applicantStorageRoot($resort_id) . '/oi-' . $oi->id;
                foreach ($request->file('offer_letter') as $file) {
                    if (!$file) continue;
                    $stored = $this->storeUploadedFile($file, $storePath);
                    // Remember the LAST one on the OI for the "Current" link.
                    $oi->offer_letter_path = $stored;
                    OfflineInterviewDocument::create([
                        'offline_interview_id' => $oi->id,
                        'category' => 'offer_letter',
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $stored,
                        'mime_type' => $file->getClientMimeType(),
                        'size_bytes' => $file->getSize(),
                        'uploaded_by' => optional($this->resort)->id,
                    ]);
                    // Resolve a local disk path for the mail attachment.
                    $absolute = \Storage::disk('public')->path($stored);
                    if (file_exists($absolute)) {
                        $newlyUploaded[] = [
                            'path' => $absolute,
                            'name' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                        ];
                    }
                }
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

            // Best-effort: file the uploaded offer letter / contract documents
            // into the new employee's File Management folder. Offline-interview
            // docs live in OfflineInterviewDocument (not ApplicantOfferContract),
            // so we file them directly via the shared primitive. Idempotent;
            // never throws.
            if ($oi->is_selected === 'Yes' && $oi->created_employee_id) {
                $emp = \App\Models\Employee::find($oi->created_employee_id);
                if ($emp) {
                    $oiDocs = \App\Models\OfflineInterviewDocument::where('offline_interview_id', $oi->id)->get();
                    foreach ($oiDocs as $oiDoc) {
                        $displayName = pathinfo((string) $oiDoc->original_name, PATHINFO_FILENAME) ?: 'Onboarding Document';
                        \App\Helpers\EmployeeDocumentFiler::fileDocToEmployeeFolder($emp, $oiDoc->file_path, $displayName);
                    }
                }
            }

            // ── Email the offer letter + contract to the candidate ─────
            // Sent only when Selected AND at least one new attachment was
            // uploaded in this submit. All files go in ONE email. Failures
            // are logged but don't break the wizard.
            $emailedTo = null;
            if ($oi->is_selected === 'Yes' && !empty($newlyUploaded)) {
                $applicant = $oi->applicant;
                $candidateEmail = $request->input('override_email')
                    ?: trim((string) optional($applicant)->email);
                if ($candidateEmail !== '') {
                    try {
                        $candidateName = trim((optional($applicant)->first_name ?? '') . ' ' . (optional($applicant)->last_name ?? '')) ?: 'Candidate';
                        $resortName    = optional(\App\Models\Resort::find($resort_id))->resort_name ?? 'the Resort';
                        $positionTitle = (string) ($oi->position_title ?: '');

                        \Mail::to($candidateEmail)->send(new \App\Mail\OfflineOfferLetterMail(
                            $candidateName,
                            $positionTitle,
                            $resortName,
                            $newlyUploaded
                        ));
                        $emailedTo = $candidateEmail;
                    } catch (\Throwable $mailErr) {
                        \Log::warning('OfflineInterview offer-letter email failed: ' . $mailErr->getMessage());
                    }
                }
            }

            $msg = $oi->is_selected === 'Yes'
                ? 'Candidate selected — employee record created. They are now visible in People with status Onboarding.'
                : 'Marked as not selected.';
            if ($emailedTo) {
                $msg .= ' Offer letter emailed to ' . $emailedTo . '.';
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
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

        // License cap — resorts.no_of_users set on the super-admin resort
        // form. Blocks the conversion once the cap is reached.
        $limitError = Common::employeeLimitError($resort_id);
        if ($limitError) {
            return ['success' => false, 'message' => $limitError, 'status' => 422];
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

        // Next sequential employee id per resort (prefix-aware, off the highest
        // existing Emp_id — NOT the table primary key).
        $emp_id = Common::nextEmployeeId($resort_id);

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
