<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeReminder;
use App\Models\Employee;
use App\Models\IncrementType;
use App\Models\ResortPosition;
use App\Models\TrainingSchedule;
use App\Models\PeopleSalaryIncrement;
use App\Models\PeopleSalaryIncrementStatus;
use App\Models\ResortDepartment;
use App\Events\ResortNotificationEvent;
use App\Helpers\Common;
use Carbon\Carbon;
use App\Exports\SalaryIncrementExport;
use Auth;
use Config;
use DB;

class SalaryIncrementController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    /**
     * Build Finance + GM approver pools for this resort.
     *
     * Mirrors the pool logic in PromotionController@initiate so the same
     * people who get notified on initiate also get notified on every
     * downstream action (approve/reject/hold/change-request).
     *
     * Returns ['finance' => Collection<Employee>, 'gm' => Collection<Employee>].
     */
    private function buildApprovalPools(int $resortId): array
    {
        $financeRank   = array_search('Finance', config('settings.Position_Rank')) ?: 7;
        $gmRank        = array_search('GM',      config('settings.Position_Rank')) ?: 8;
        $financeTitles = ['Director of Finance', 'Finance Manager'];

        $financeDeptIds = ResortDepartment::where('resort_id', $resortId)
            ->get()
            ->filter(fn($d) => Common::isFinanceDepartment($d->id))
            ->pluck('id');

        $financePool = Employee::with('resortAdmin')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where(function ($q) use ($financeRank, $financeTitles, $financeDeptIds) {
                $q->where('rank', $financeRank)
                  ->orWhereHas('position', function ($pq) use ($financeRank, $financeTitles) {
                      $pq->where('Rank', $financeRank)
                         ->orWhereIn('position_title', $financeTitles);
                  });
                if ($financeDeptIds->isNotEmpty()) {
                    $q->orWhere(function ($qq) use ($financeDeptIds) {
                        $qq->whereIn('Dept_id', $financeDeptIds)
                           ->whereIn('rank', [1, 2]);
                    });
                }
            })
            ->get();

        $gmPool = Employee::with('resortAdmin')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where(function ($q) use ($gmRank) {
                $q->where('rank', $gmRank)
                  ->orWhereHas('position', function ($pq) use ($gmRank) {
                      $pq->where('Rank', $gmRank);
                  });
            })
            ->get();

        return ['finance' => $financePool, 'gm' => $gmPool];
    }

    /**
     * Apply an Approved salary increment to the employee's record —
     * sets basic_salary, incremented_date, last increment amount/type,
     * notes. Stamps effective_day_applied_at on the increment so the
     * daily command treats this row as done.
     *
     * Static so both `updateStatus` (immediate apply when the GM
     * approves a same-day increment) and
     * App\Console\Commands\ApplyEffectiveSalaryIncrement (daily
     * catch-up for future-dated rows) can call the same path.
     */
    public static function applyApprovedIncrementToEmployee(PeopleSalaryIncrement $increment): bool
    {
        if ($increment->status !== 'Approved') return false;
        if (!empty($increment->effective_day_applied_at)) return false; // already applied

        $employee = Employee::find($increment->employee_id);
        if (!$employee) return false;

        $employee->update([
            'basic_salary'                  => $increment->new_salary,
            'incremented_date'              => $increment->effective_date,
            'last_increment_salary_amount'  => $increment->increment_amount,
            'last_salary_increment_type'    => $increment->increment_type,
            'notes'                         => $increment->remarks,
        ]);

        $increment->update(['effective_day_applied_at' => now()]);
        return true;
    }

    /**
     * Fan out notifications for any state transition on a salary increment.
     *
     * Recipients depend on the action + the stage that acted:
     *   - Approved by Finance     → GM pool (next stage) + HR + subject employee
     *   - Approved by GM (final)  → HR + subject employee
     *   - Rejected (any stage)    → HR + subject employee
     *   - Hold (any stage)        → HR + subject employee
     *   - Change-Request (any)    → HR (they need to fix + re-submit)
     *
     * Dedupes by employee id and drops the subject employee from any
     * approver pool (self-approval guard, same as bulkStore).
     */
    private function dispatchActionNotifications(
        PeopleSalaryIncrement $increment,
        string $action,
        ?string $actorRank,
        ?string $remarks
    ): void {
        $resortId = (int) $this->resort->resort_id;
        $pools    = $this->buildApprovalPools($resortId);

        $subject = Employee::with('resortAdmin')->find($increment->employee_id);
        if (!$subject) return;

        // HR = the resort-admin who initiated the request → its Employee row.
        $hr = $increment->created_by
            ? Employee::with('resortAdmin')->where('Admin_Parent_id', $increment->created_by)->first()
            : null;

        // HR department pool — HOD (rank 2) + EXCOM (rank 1) of any
        // department flagged as HR by Common::isHRDepartment(). Used on
        // every Approved action so the entire HR dept (not only the
        // initiator) is in the loop when Finance or GM signs off. Was
        // previously notifying only the single resort-admin who created
        // the increment — HR HOD/EXCOM had to refresh the list manually.
        $hrDeptIds = ResortDepartment::where('resort_id', $resortId)
            ->get()
            ->filter(fn($d) => Common::isHRDepartment($d->id))
            ->pluck('id');
        $hrPool = collect();
        if ($hrDeptIds->isNotEmpty()) {
            $hrPool = Employee::with('resortAdmin')
                ->where('resort_id', $resortId)
                ->where('status', 'Active')
                ->whereIn('Dept_id', $hrDeptIds)
                ->whereIn('rank', [1, 2])
                ->get();
        }

        $employeeName  = optional($subject->resortAdmin)->full_name ?: '';
        $effectiveFmt  = !empty($increment->effective_date)
            ? Carbon::parse($increment->effective_date)->format('d M Y')
            : '-';
        $reasonLine    = trim((string) $remarks) !== ''
            ? "\n💬 Reason: " . trim((string) $remarks)
            : '';

        // Icon + title per action so the bell list is scannable.
        $titlesIcons = [
            'Approved'       => ['📢 Salary Increment Approved',        '✅'],
            'Rejected'       => ['📢 Salary Increment Rejected',        '❌'],
            'Hold'           => ['📢 Salary Increment Put On Hold',     '⏸️'],
            'Change-Request' => ['📢 Salary Increment Change Requested','✏️'],
        ];
        $title = $titlesIcons[$action][0] ?? '📢 Salary Increment Update';
        $icon  = $titlesIcons[$action][1] ?? '📢';

        $stageLabel = $actorRank ?: 'System';
        $msg = "{$icon} Salary increment for {$employeeName}"
             . " was marked '{$action}' by {$stageLabel}."
             . "\n📅 Effective Date: " . $effectiveFmt
             . "\n💰 New Salary: " . number_format((float) $increment->new_salary, 2)
             . $reasonLine;

        // Build recipient list per action.
        $recipients = collect();
        switch ($action) {
            case 'Approved':
                if ($actorRank === 'Finance') {
                    $recipients = $recipients->merge($pools['gm']);   // next stage
                }
                // HR dept HOD + EXCOM get pinged on EVERY Approved
                // action (Finance approval AND GM final approval), so
                // the whole HR department stays informed without
                // refreshing the list. Per-user request.
                $recipients = $recipients
                    ->merge($hrPool)
                    ->push($hr)
                    ->push($subject);
                break;
            case 'Rejected':
            case 'Hold':
                $recipients = $recipients->push($hr)->push($subject);
                break;
            case 'Change-Request':
                $recipients = $recipients->push($hr);
                break;
        }

        // Self-approval guard + dedupe by employee id.
        $subjectId = (int) $subject->id;
        $sent      = [];
        foreach ($recipients as $member) {
            if (!$member) continue;
            $mid = (int) $member->id;
            if ($mid === $subjectId && in_array($action, ['Approved','Rejected','Hold','Change-Request'], true)
                && $actorRank !== null) {
                // The subject IS allowed to be notified (they're the
                // affected party); but if they happen to also be in the
                // approver pool, don't double-fire — handled by dedupe.
            }
            if (isset($sent[$mid])) continue;
            $sent[$mid] = true;

            event(new ResortNotificationEvent(Common::nofitication(
                $resortId,
                10,
                $title,
                $msg,
                0,
                $mid,
                'People'
            )));
        }
    }

    // Employee List view and show if any update request is pending
    public function index()
    {
        $page_title = 'Salary Increment Management';
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();
         $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES;
        return view('resorts.people.salary-increment.includes.list', compact('page_title','incrementTypes','payIncreaseTypes'));
    }
   

    public function list(Request $request){
        if($request->ajax())
        {
            // Include Hold rows so HR can still see (and edit) increments
            // that Finance/GM has paused. Hold locks the bulk-action footer
            // on /summary-list but doesn't make the row invisible to HR.
            $query = PeopleSalaryIncrement::where('resort_id',$this->resort->resort_id)
            ->whereIn('status',['Pending','Change-Request','Hold'])
            ->select('id', 'employee_id', 'increment_type', 'effective_date', 'value','pay_increase_type', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status','created_at')
                ->with([
                    'employee.resortAdmin:id,first_name,last_name',
                    'employee.department:id,name',
                    'employee.position:id,position_title',
                    // Eager-load the per-stage status rows so the action
                    // column can hide Edit once Finance/GM approves without
                    // firing an N+1 query per table row.
                    'peopleSalaryIncrementStatusFinance:id,people_salary_increment_id,status,remarks',
                    'peopleSalaryIncrementStatusGM:id,people_salary_increment_id,status,remarks',
                ])
                ->whereHas('employee', function ($q) {
                    $q->where('resort_id', $this->resort->resort_id);
                })->get();

                
            $currentBasicSalary = (clone $query)->sum('previous_salary');
            $newBasicSalary = (clone $query)->sum('new_salary');
            $monthlyPayrollIncrease = $newBasicSalary - $currentBasicSalary;
            $annualPayrollIncrease = $monthlyPayrollIncrease * 12;
            
            $edit_class = '';
            if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            return datatables()->of($query)
                ->addColumn('Emp_id', function($row){
                    return optional($row->employee)->Emp_id ?? '-';
                })
                ->addColumn('employee_name', function($row){
                    return optional(optional($row->employee)->resortAdmin)->full_name ?? '-';
                })
                ->addColumn('position_title', function($row){
                    return optional(optional($row->employee)->position)->position_title ?? '-';
                })
                ->addColumn('department_name', function($row){
                    return optional(optional($row->employee)->department)->name ?? '-';
                })
                ->editColumn('effective_date', function($query){
                    return $query->effective_date ? Carbon::parse($query->effective_date)->format('d M Y') : '-';
                })
                ->addColumn('last_activity', function($query){
                    // Render each stage's status as a coloured badge using the
                    // same theme classes the Promotion/Transfer modules use:
                    //   Approved  → badge-themeSuccess  (green)
                    //   Pending   → badge-themeWarning  (yellow)
                    //   Rejected  → badge-themeDanger   (red)
                    //   Hold      → badge-themeSkyblue  (cyan)
                    //
                    // NOTE: the stored status is `Hold` (not `On Hold`) —
                    // the old map matched the wrong string so held stages
                    // fell through to the default yellow.
                    $badgeFor = function (string $status): string {
                        return match (trim($status)) {
                            'Approved'       => 'badge-themeSuccess',
                            'Rejected'       => 'badge-themeDanger',
                            'Hold'           => 'badge-themeSkyblue',
                            'On Hold'        => 'badge-themeSkyblue',
                            'Change-Request' => 'badge-themeSkyblue',
                            default          => 'badge-themeWarning',
                        };
                    };
                    // Render the per-stage rows at a compact size so the
                    // "Finance: Pending / GM: Pending" stack doesn't dwarf
                    // the rest of the DataTable row.
                    //
                    // IMPORTANT: this listing page is wrapped in
                    // `.card-salaryIncrementSum`, and default.css contains
                    //   .card-salaryIncrementSum strong { font-size: 26px; }
                    // so `<strong>` here renders 26px regardless of the
                    // wrapper's font-size. We use `<span>` with explicit
                    // weight/size instead, and `!important` on the badge to
                    // beat the theme's `.badge { font-size: 14px }` rule.
                    $row = function (string $label, $stage) use ($badgeFor) {
                        if (!$stage) return '';
                        $status   = e((string) $stage->status);
                        $remarks  = !empty($stage->remarks) ? ' &mdash; ' . e($stage->remarks) : '';
                        return '<div class="mb-1" style="font-size:12px !important; line-height:1.3 !important;">'
                            . '<span style="font-weight:600; font-size:12px !important;">' . e($label) . ':</span> '
                            . '<span class="badge ' . $badgeFor($stage->status) . '" style="font-size:11px !important; font-weight:500 !important; padding:2px 6px !important;">' . $status . '</span>'
                            . '<span style="font-size:12px !important;">' . $remarks . '</span>'
                            . '</div>';
                    };
                    return $row('Finance', $query->peopleSalaryIncrementStatusFinance)
                         . $row('GM',      $query->peopleSalaryIncrementStatusGM);
                })
                ->addColumn('action', function($query) use ($edit_class) {
                    // View is always available — read-only modal showing the
                    // initiator + approval chain + pending stage. Same modal
                    // for everyone (HR, Finance, GM) so anyone can see the
                    // full trail without paging another module.
                    $viewBtn = '<a href="' . route('people.salary-increment.view', $query->id) . '"
                        data-bs-toggle="modal" data-bs-target="#viewData-modal"
                        class="a-linkTheme open-ajax-view-modal me-2"
                        title="View approval chain">
                        <i class="fa-solid fa-eye"></i>
                    </a>';

                    // Once EITHER Finance or GM has approved the row, hide the
                    // Edit button — HR shouldn't be able to mutate values that
                    // an approver has already signed off on. Rows where both
                    // stages are still Pending (or Change-Request) keep Edit.
                    $financeApproved = optional($query->peopleSalaryIncrementStatusFinance)->status === 'Approved';
                    $gmApproved      = optional($query->peopleSalaryIncrementStatusGM)->status === 'Approved';
                    if ($financeApproved || $gmApproved) {
                        return '<div class="d-flex align-items-center">' . $viewBtn . '</div>';
                    }
                    return '
                        <div class="d-flex align-items-center">
                           ' . $viewBtn . '
                           <a href="' . route('people.salary-increment.edit', $query->id) . '" data-bs-toggle="modal" data-bs-target="#editData-modal" class="a-linkTheme open-ajax-modal ' . $edit_class . '"> <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid"></a>
                        </div>';
                })
                ->with([
                    'currentBasicSalary' => $currentBasicSalary,
                    'newBasicSalary' => $newBasicSalary,
                    'monthlyPayrollIncrease' => $monthlyPayrollIncrease,
                    'annualPayrollIncrease' => $annualPayrollIncrease
                ])
                ->rawColumns(['Emp_id','department_name','employee_name','position_title','last_activity','action'])
                ->make(true);
        }
    }

    public function edit($id)
    {
        if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.edit')) == false){
            return abort(403, 'Unauthorized access');
        }
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();
        // Was ->find($id) with no resort filter — unscoped read leaked
        // another resort's increment (salary, employee) into this modal.
        $peopleSalaryIncrement = PeopleSalaryIncrement::with(['employee.resortAdmin:id,first_name,last_name', 'employee.department:id,name', 'employee.position:id,position_title'])
            ->where('resort_id', $this->resort->resort_id)
            ->find($id);
        if (!$peopleSalaryIncrement) {
            return response()->json(['status' => 'error', 'message' => 'Not found.'], 404);
        }
        $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES;
         $html = view('resorts.people.salary-increment.includes.edit-modal', ['peopleSalaryIncrement'=>$peopleSalaryIncrement,'incrementTypes'=>$incrementTypes,'payIncreaseTypes'=>$payIncreaseTypes])->render();

        return response()->json(['status' => 'success', 'message' => 'get.','html'=> $html]);
    }

    /**
     * Return a read-only modal showing the full approval trail for one
     * increment row — initiator + every per-stage status with timestamps
     * + reasons + current "pending from" stage. Used by the View button on
     * the HR listing so anyone can see who raised the request and which
     * approver is holding it up without having to ping someone in Slack.
     */
    public function viewDetail($id)
    {
        // Was ->find($id) with no resort filter — unscoped read leaked
        // another resort's full approval trail (salary + reasons).
        $peopleSalaryIncrement = PeopleSalaryIncrement::with([
            'employee.resortAdmin:id,first_name,last_name',
            'employee.department:id,name',
            'employee.position:id,position_title',
            'peopleSalaryIncrementStatusFinance',
            'peopleSalaryIncrementStatusGM',
        ])->where('resort_id', $this->resort->resort_id)->find($id);

        if (!$peopleSalaryIncrement) {
            return response()->json(['status' => 'error', 'message' => 'Not found.'], 404);
        }

        // Resolve initiator — created_by is a resort_admin id; map to the
        // ResortAdmin so we can show their full name + when they raised it.
        $initiator = null;
        if (!empty($peopleSalaryIncrement->created_by)) {
            $initiator = \App\Models\ResortAdmin::select('id','first_name','last_name')
                ->where('id', $peopleSalaryIncrement->created_by)->first();
        }

        $html = view('resorts.people.salary-increment.includes.view-modal', [
            'peopleSalaryIncrement' => $peopleSalaryIncrement,
            'initiator'             => $initiator,
        ])->render();

        return response()->json(['status' => 'success', 'html' => $html]);
    }


    public function update(Request $request,$id){

        // Was ->find($id) with no resort filter — unscoped write let a
        // foreign resort's increment id/value/salary be overwritten and
        // its approval chain reset to Pending.
        $peopleSalaryIncrement = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$peopleSalaryIncrement) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => 'Not found.'], 404);
        }

        $effectiveDate = Carbon::createFromFormat('d/m/Y', $request->effective_date)->format('Y-m-d');

        if ($peopleSalaryIncrement) {
            // Track whether the row was in a "stalled" state before the
            // edit so we know whether to re-open the chain + re-notify
            // Finance afterwards. Stalled = Rejected, Hold, or
            // Change-Request — anything that needs HR to fix the values
            // and bounce it back through the approval pipeline.
            $wasStalled = in_array(
                (string) $peopleSalaryIncrement->status,
                ['Rejected', 'Hold', 'Change-Request'],
                true
            );

            // Default to Fixed when the form no longer sends the field.
            $payIncreaseType = $request->pay_increase_type ?: PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED;
            if($payIncreaseType == PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE){
               $value = $peopleSalaryIncrement->previous_salary * $request->value / 100;
            } else {
                $value = $request->value;
            }

            $peopleSalaryIncrement->update([
                'increment_type' => $request->increment_type,
                'pay_increase_type' => $payIncreaseType,
                'value' => $request->value,
                'new_salary' => $peopleSalaryIncrement->previous_salary + $value,
                'increment_amount' => $value,
                'effective_date' => $effectiveDate,
                'remark'=> $request->remark,
            ]);

            if ($wasStalled) {
                // Re-open the approval chain so Finance / GM can act
                // again. Clear due_date (was a hold), bounce the
                // increment back to Pending, and reset every per-stage
                // status row to Pending with cleared remarks / actor /
                // action timestamp. Without this, even after HR fixes
                // the values the summary-list still shows it as Hold /
                // Rejected / Change-Request and the action buttons stay
                // hidden.
                $peopleSalaryIncrement->update([
                    'status'   => 'Pending',
                    'due_date' => null,
                ]);
                PeopleSalaryIncrementStatus::where('people_salary_increment_id', $peopleSalaryIncrement->id)
                    ->update([
                        'status'        => 'Pending',
                        'approved_by'   => null,
                        'action_date'   => null,
                        'remarks'       => null,
                        'reject_reason' => null,
                    ]);

                // Re-notify the Finance pool (entry stage) so they see
                // the re-submitted request — same fan-out shape as
                // bulkStore but without going through the initiate
                // flow. GM doesn't get notified at this point; they
                // only get pinged when Finance approves.
                $pools = $this->buildApprovalPools(
                    (int) $this->resort->resort_id
                );

                $subject = Employee::with('resortAdmin')->find($peopleSalaryIncrement->employee_id);
                if ($subject) {
                    $employeeName  = optional($subject->resortAdmin)->full_name ?: '';
                    $positionTitle = optional($subject->position)->position_title ?? '';
                    $effectiveFmt  = !empty($peopleSalaryIncrement->effective_date)
                        ? Carbon::parse($peopleSalaryIncrement->effective_date)->format('d M Y')
                        : '-';
                    $msg = "📢 Salary Increment Re-Submitted by HR"
                         . "\n👤 Employee: " . $employeeName
                         . "\n💼 Position: " . $positionTitle
                         . "\n💰 New Salary: " . number_format((float) $peopleSalaryIncrement->new_salary, 2)
                         . "\n📅 Effective Date: " . $effectiveFmt
                         . "\n📝 Status: Pending Approval (resubmitted)";

                    $resortId  = (int) $this->resort->resort_id;
                    $subjectId = (int) $subject->id;
                    foreach ($pools['finance'] as $member) {
                        if ((int) $member->id === $subjectId) continue;
                        event(new ResortNotificationEvent(Common::nofitication(
                            $resortId,
                            10,
                            'Salary Increment Re-Submitted',
                            $msg,
                            0,
                            $member->id,
                            'People'
                        )));
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'People Salary updated successfully.'
        ]);
    }

    // grid View where update salary bulk action 
     public function gridIndex()
    {
        if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        $page_title = 'Salary Increment Management';
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();
        $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES; 

        return view('resorts.people.salary-increment.index', compact('page_title','incrementTypes','payIncreaseTypes'));
    }

    public function employeeGridView(Request $request)
    {
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $query = Employee::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')->where('basic_salary', '>', 0)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));
        if ($request->search) {
           $query->whereHas('resortAdmin', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%');
            });
        }

        // The 4 filters below mirror the working logic in
        // PromotionController@GetEmployeeWiseFilterData. Each computes the
        // set of employee_ids to drop, then applies whereNotIn so the
        // remaining query stays paginatable.
        $resortId = (int) $this->resort->resort_id;
        $today = now()->toDateString();
        $threeMonthsAgo = now()->subMonths(3)->toDateString();

        if ((int) $request->exclude_probation === 1) {
            // "On probation" = employee is still in their probation window —
            // probation_end_date is in the future, OR (no end_date AND joined
            // within the last 3 months). Matches the promotion module spec.
            $probationerIds = Employee::where('resort_id', $resortId)
                ->where('status', 'Active')
                ->where(function ($q) use ($today, $threeMonthsAgo) {
                    $q->whereDate('probation_end_date', '>=', $today)
                      ->orWhere(function ($q2) use ($threeMonthsAgo) {
                          $q2->whereNull('probation_end_date')
                             ->whereDate('joining_date', '>=', $threeMonthsAgo);
                      });
                })
                ->pluck('id')->all();
            if (!empty($probationerIds)) {
                $query->whereNotIn('id', $probationerIds);
            }
        }

        if ((int) $request->exclude_disciplinary === 1) {
            // disciplinary_submits.status values are 'Pending', 'In_Review'
            // (capital + underscore — the old lowercase array never matched).
            $disciplinaryIds = Employee::whereHas('disciplinarySubmits', function ($q) use ($resortId) {
                $q->where('status', 'In_Review')
                  ->where('resort_id', $resortId);
            })->pluck('id')->all();
            if (!empty($disciplinaryIds)) {
                $query->whereNotIn('id', $disciplinaryIds);
            }
        }

        if ((int) $request->exclude_recent_promotion === 1) {
            // Anyone with an Approved promotion effective within the last 6 months.
            $promotedIds = \App\Models\EmployeePromotion::where('resort_id', $resortId)
                ->where('status', 'Approved')
                ->where('effective_date', '>=', now()->subMonths(6)->toDateString())
                ->pluck('employee_id')->all();
            if (!empty($promotedIds)) {
                $query->whereNotIn('id', $promotedIds);
            }
        }

        if ((int) $request->exclude_no_training === 1) {
            // Probationers whose mandatory onboarding programs aren't all
            // Completed (Present/Late attendance). Long-tenured employees
            // aren't subject to this — same scoping as the promotion filter.
            $requiredProgramIds = \DB::table('probationary_learning_programs')
                ->where('resort_id', $resortId)
                ->pluck('program_id')->unique()->all();

            $probationerIds = Employee::where('resort_id', $resortId)
                ->where('status', 'Active')
                ->where(function ($q) use ($today, $threeMonthsAgo) {
                    $q->whereDate('probation_end_date', '>=', $today)
                      ->orWhere(function ($q2) use ($threeMonthsAgo) {
                          $q2->whereNull('probation_end_date')
                             ->whereDate('joining_date', '>=', $threeMonthsAgo);
                      });
                })
                ->pluck('id')->all();

            if (!empty($requiredProgramIds) && !empty($probationerIds)) {
                $completedByEmp = \DB::table('training_participants as tp')
                    ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
                    ->leftJoin('training_attendance as ta', function ($j) {
                        $j->on('ta.training_schedule_id', '=', 'tp.training_schedule_id')
                          ->on('ta.employee_id', '=', 'tp.employee_id');
                    })
                    ->where('ts.resort_id', $resortId)
                    ->whereIn('ts.training_id', $requiredProgramIds)
                    ->whereRaw("COALESCE(ta.status, tp.status) IN ('Present','Late')")
                    ->whereIn('tp.employee_id', $probationerIds)
                    ->select('tp.employee_id', 'ts.training_id')
                    ->get()
                    ->groupBy('employee_id')
                    ->map(fn($rows) => $rows->pluck('training_id')->unique()->all())
                    ->toArray();

                $notCompletedIds = [];
                foreach ($probationerIds as $empId) {
                    $empCompleted = $completedByEmp[$empId] ?? [];
                    if (count($empCompleted) < count($requiredProgramIds)) {
                        $notCompletedIds[] = $empId;
                    }
                }
                if (!empty($notCompletedIds)) {
                    $query->whereNotIn('id', $notCompletedIds);
                }
            }
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(15);
        $employee_count = $employees->count();
        $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES;
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();

        // Pre-compute the budgeted salary per employee so the card can warn
        // HR when the proposed new salary exceeds the position's budget.
        // Mirrors the promotion module's "exceeds budget" check.
        foreach ($employees as $emp) {
            $emp->budgeted_salary = \App\Helpers\Common::computeBudgetedSalaryForPosition(
                (int) $this->resort->resort_id,
                (int) $emp->Position_id,
                $emp->position
            );
        }

        $html = view('resorts.people.salary-increment.includes.grid-view', compact('employees','incrementTypes','payIncreaseTypes'))->render();
        return response()->json([
            'success' => true,
            'status' => 'success',
            'employee_count' => $employee_count,
            'html' => $html,
        ]);
    }

    // This  two summary function are used to show dat to hr
    public function summaryStore(Request $request)
    {

        $request->validate([
            'increments.*.emp_id' => 'required',
            'increments.*.increment_type' => 'required',
            // pay_increase_type is now hidden (always 'Fixed') and therefore
            // optional. Old payloads that still send it are accepted.
            'increments.*.pay_increase_type' => 'nullable',
            'increments.*.value' => 'required|numeric|min:0',
            'increments.*.effective_date' => 'required',
            'increments.*.remark' => 'nullable|string',
        ]);

        $arr_increments = []; // Initialize an array to store increment data

        foreach ($request->increments as $inc) {
            // Was Employee::find($inc['emp_id']) with no resort filter —
            // unscoped read pulled a foreign resort's employee salary/
            // identity into this resort's session summary view.
            $employee = Employee::where('resort_id', $this->resort->resort_id)->find($inc['emp_id']);
            if (!$employee) {
                continue;
            }

            $effectiveDate = Carbon::createFromFormat('d/m/Y', $inc['effective_date'])->format('Y-m-d');
            // Default to Fixed when the field is missing (UI no longer shows it).
            $payIncreaseType = $inc['pay_increase_type'] ?? PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED;
            if($payIncreaseType == PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE){
                $incrementAmount = $employee->basic_salary * $inc['value'] / 100;
            } else {
                $incrementAmount = $inc['value'] ;
            }

            // GetAdminResortProfile crashes on orphaned Admin_Parent_id
            // AND doesn't validate the file exists — returns a path that
            // 404s, producing the broken-image icon HR saw on this page.
            // Use the modern helper that returns the default picture URL
            // when the chain is incomplete.
            $employee_image = Common::getResortUserPicture($employee->Admin_Parent_id ?? null);
            $arr_increments[] = [
                'emp_id' => $inc['emp_id'],
                'employee_code' => $employee->Emp_id,
                'employee_image' => $employee_image,
                'employee_name' => $employee->resortAdmin->full_name,
                'employee_position' => $employee->position->position_title,
                'employee_department' => $employee->department->name,
                'increment_type' => $inc['increment_type'],
                'effective_date' => $effectiveDate,
                'pay_increase_type' => $payIncreaseType,
                'value' => $inc['value'],
                'previous_salary' => $employee->basic_salary,
                'new_salary' => $employee->basic_salary + $incrementAmount,
                'increment_amount' => $incrementAmount,
                'remark' => $inc['remark'],
            ];
        }

        // Store the increments data in the session to pass to the next page
        session(['increments_summary' => $arr_increments]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'redirect_url' => route('people.salary-increment.summary-view'),
        ]);
    }

    public function summaryView()
    {
        $page_title = 'Salary Increment Summary';
        $employees_data = session('increments_summary', []); 
        $currentBasicSalary = 0;
        $newSalary = 0;
        $totalEmployees = count($employees_data);
        foreach ($employees_data as $key => $employee) {
            $currentBasicSalary +=$employee['previous_salary'];
            $newSalary +=$employee['new_salary'];
        }
        $monthlyDifference = $newSalary - $currentBasicSalary;
        $yearlyDifference = $monthlyDifference * 12;

        return view('resorts.people.salary-increment.summary-view', compact('page_title', 'employees_data','totalEmployees','currentBasicSalary','newSalary','monthlyDifference','yearlyDifference'));
    }

    // store data in PeopleSalaryIncrement and PeopleSalaryIncrementStatus table
    public function bulkStore(Request $request)
    {
        $approvalRank = [
            'Finance',
            'GM'
        ];

        // Build the Finance + GM approver pools ONCE so every increment in
        // the bulk submit notifies the same people. Mirrors the pool-based
        // fan-out in PromotionController@initiate so Finance HOD / EXCOM
        // (and not just the Director of Finance) actually see the request.
        $resortId      = (int) $this->resort->resort_id;
        $financeRank   = array_search('Finance', config('settings.Position_Rank')) ?: 7;
        $gmRank        = array_search('GM',      config('settings.Position_Rank')) ?: 8;
        $financeTitles = ['Director of Finance', 'Finance Manager'];

        $financeDeptIds = ResortDepartment::where('resort_id', $resortId)
            ->get()
            ->filter(fn($d) => Common::isFinanceDepartment($d->id))
            ->pluck('id');

        $financePool = Employee::with('resortAdmin')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where(function ($q) use ($financeRank, $financeTitles, $financeDeptIds) {
                $q->where('rank', $financeRank)
                  ->orWhereHas('position', function ($pq) use ($financeRank, $financeTitles) {
                      $pq->where('Rank', $financeRank)
                         ->orWhereIn('position_title', $financeTitles);
                  });
                if ($financeDeptIds->isNotEmpty()) {
                    // Pull in Finance dept HOD (rank=2) + EXCOM (rank=1) even
                    // when their position title isn't DOF / Finance Manager.
                    $q->orWhere(function ($qq) use ($financeDeptIds) {
                        $qq->whereIn('Dept_id', $financeDeptIds)
                           ->whereIn('rank', [1, 2]);
                    });
                }
            })
            ->get();

        $gmPool = Employee::with('resortAdmin')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where(function ($q) use ($gmRank) {
                $q->where('rank', $gmRank)
                  ->orWhereHas('position', function ($pq) use ($gmRank) {
                      $pq->where('Rank', $gmRank);
                  });
            })
            ->get();

        foreach ($request->employee_data as $inc) {

            // Most severe finding in this file per the audit: was
            // Employee::find($inc['emp_id']) with no resort filter — an
            // attacker could pass a foreign resort's employee id here and
            // this would create a REAL, persisted PeopleSalaryIncrement row
            // (tagged with the attacker's own resort_id) against that
            // employee. If later approved via updateStatus(), that writes
            // the new salary onto the foreign employee's real record.
            $employee = Employee::where('resort_id', $resortId)->find($inc['emp_id']);
            if (!$employee) {
                continue;
            }
            $chk_people_salary_increment = PeopleSalaryIncrement::where('employee_id', $inc['emp_id'])
                ->where('status','Pending')->first();
            if(!$chk_people_salary_increment){
                $increment = PeopleSalaryIncrement::Create(
                    [
                        'resort_id' => $resortId,
                        'employee_id' => $inc['emp_id'],
                        'increment_type' => $inc['increment_type'],
                        'effective_date' => $inc['effective_date'],
                        'pay_increase_type' => $inc['pay_increase_type'],
                        'value' => $inc['value'],
                        'previous_salary' => $inc['previous_salary'],
                        'new_salary' => $inc['new_salary'],
                        'increment_amount' => $inc['increment_amount'],
                        'remarks' => $inc['remark'],
                        'status' => 'Pending',
                    ]
                );

                foreach($approvalRank as $approval){
                    PeopleSalaryIncrementStatus::create([
                        'people_salary_increment_id' => $increment->id,
                        'approval_rank' => $approval,
                        'status' => 'Pending',
                    ]);
                }

                // Fan-out notifications to every Finance + GM pool member —
                // this is what was missing: only the DOF was being implicitly
                // expected to see these, so Finance HOD / EXCOM never got
                // pinged. Self-approval guard drops the employee being
                // incremented so they don't get notified about themselves.
                $employeeBeingIncrementedId = (int) $increment->employee_id;
                $employeeName = optional(optional($employee)->resortAdmin)->full_name ?? '';
                $positionTitle = optional($employee->position)->position_title ?? '';
                $effectiveFmt = !empty($inc['effective_date'])
                    ? Carbon::parse($inc['effective_date'])->format('d M Y')
                    : '-';
                $msg = "📢 New Salary Increment Submitted"
                     . "\n👤 Employee: " . $employeeName
                     . "\n💼 Position: " . $positionTitle
                     . "\n💰 New Salary: " . number_format((float) $inc['new_salary'], 2)
                     . "\n📅 Effective Date: " . $effectiveFmt
                     . "\n📝 Status: Pending Approval";

                $notifyPool = function ($pool) use (
                    $resortId, $msg, $employeeBeingIncrementedId
                ) {
                    foreach ($pool as $member) {
                        if ((int) $member->id === $employeeBeingIncrementedId) continue;
                        event(new ResortNotificationEvent(Common::nofitication(
                            $resortId,
                            10,
                            'Salary Increment Request Notification',
                            $msg,
                            0,
                            $member->id,
                            'People'
                        )));
                    }
                };
                $notifyPool($financePool);
                $notifyPool($gmPool);
            }
        }

        return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Increments saved successfully.',
                'redirect_url' => route('people.salary-increment.index'),
            ]);
    }

    public function bulkUpdate(Request $request){
        $ids = $request->selected_ids;
        $effectiveDate = Carbon::createFromFormat('d/m/Y', $request->effective_date)->format('Y-m-d');
        // Default to Fixed when the form no longer sends the field.
        $payIncreaseType = $request->pay_increase_type ?: PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED;

        foreach ($ids as $id) {
            // Was ->find($id) with no resort filter — unscoped write let
            // client-posted ids from another resort be mutated in bulk.
            $peopleSalaryIncrement = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->find($id);
            if (!$peopleSalaryIncrement) {
                continue;
            }
            if($payIncreaseType == PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE){
               $value = $peopleSalaryIncrement->previous_salary * $request->value / 100;
            } else {
                $value = $request->value;
            }

            $peopleSalaryIncrement->update([
                'increment_type' => $request->increment_type,
                'pay_increase_type' => $payIncreaseType,
                'value' => $request->value,
                'new_salary' => $peopleSalaryIncrement->previous_salary + $value, 
                'increment_amount' => $value,
                'effective_date' => $effectiveDate,
                'remark'=> $request->remark,
            ]);
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'People Salary updated successfully.'
        ]);
    }

    public function bulkUpdateStatus(Request $request){
        $ids = $request->ids;
        
        foreach ($ids as $id) {
            // Was ->find($id) with no resort filter — unscoped write let
            // client-posted ids from another resort be mutated in bulk.
            $peopleSalaryIncrement = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->find($id);
            if (!$peopleSalaryIncrement) {
                continue;
            }

            $peopleSalaryIncrement->update([
                'status'=> 'Pending'
            ]);
            $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $id)->where('status','Change-Request')->first();
            if($peopleSalaryIncrementStatus){
                $peopleSalaryIncrementStatus->update([
                    'status'=> 'Pending'
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'People Salary updated successfully.'
        ]);
    }

    // below function is used to  Finance and GM view only 
    public function summaryIndex(Request $request)
    {
        $page_title = 'Salary Increment Summary';
            $hasFinanceApproval = false;
            $hasGMApproval = false;
            $downloadBtn = false;
            // True only when at least one row is currently Pending (not
            // already Hold). The "On Hold" button is meaningless once the
            // visible row is already held, so we hide it in that case;
            // Approve / Reject / Request Change stay available.
            $showHoldButton = false;

        // Role detection moved BEFORE the query so we can scope the visible
        // set to rows where THIS user still has an action to take. Without
        // this, Finance saw rows they'd already approved, clicked Approve a
        // second time, and re-fired the GM notification each click. Same
        // for GM seeing GM-approved rows. Approved-by-self rows now move
        // straight to the approver's own history page.
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');

        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

        $gmApprover = Employee::with('position')->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

            $currentEmpId = $this->resort->GetEmployee->id ?? null;
            // Check direct match OR delegation authority for Finance/GM approver
            $isFinanceOrDelegate = $financeApprover && ($financeApprover->id == $currentEmpId || \App\Helpers\Common::hasDelegationAuthority($currentEmpId, $financeApprover->id, $this->resort->resort_id));
            $isGMOrDelegate = $gmApprover && ($gmApprover->id == $currentEmpId || \App\Helpers\Common::hasDelegationAuthority($currentEmpId, $gmApprover->id, $this->resort->resort_id));

            // Build the base in-progress set, then per-role scope so a stage
            // that's already finished its turn drops off the user's list.
            $baseQuery = PeopleSalaryIncrement::whereIn('status', ['Pending', 'Hold', 'Change-Request'])
                ->where('resort_id', $this->resort->resort_id);

            if ($isFinanceOrDelegate) {
                // Only rows where the Finance stage is still actionable.
                $baseQuery->whereHas('peopleSalaryIncrementStatusFinance', function ($q) {
                    $q->whereIn('status', ['Pending', 'Hold', 'Change-Request']);
                });
            } elseif ($isGMOrDelegate) {
                // Only rows where Finance already approved AND GM stage is
                // still actionable. Without the Finance gate, GM would see
                // rows that haven't reached them yet.
                $baseQuery->whereHas('peopleSalaryIncrementStatusFinance', function ($q) {
                        $q->where('status', 'Approved');
                    })
                    ->whereHas('peopleSalaryIncrementStatusGM', function ($q) {
                        $q->whereIn('status', ['Pending', 'Hold', 'Change-Request']);
                    });
            }
            // For everyone else (e.g. HR — who creates increments but doesn't
            // approve them) the unfiltered in-progress set stays. HR needs to
            // see what's still in flight regardless of which stage it's at.

            $ids = $baseQuery->pluck('id');

            $query = PeopleSalaryIncrement::whereIn('id', $ids)->whereIn('status',['Pending','Hold','Change-Request'])
                ->select('id', 'employee_id', 'increment_type', 'effective_date', 'value', 'pay_increase_type', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status', 'due_date', 'created_at')
                ->with([
                    'employee.resortAdmin:id,first_name,last_name',
                    'employee.department:id,name',
                    'employee.position:id,position_title',
                    // Eager-load per-stage rows so the Status column can show
                    // the Hold reason / Change-Request reason + the actor
                    // ("Hold By", "Approved By", "Rejected By") without N+1.
                    //
                    // We pull BOTH approved_by (set by every transition going
                    // forward) AND modified_by (auto-set by the model's
                    // save hook on EVERY update — used as a fallback for
                    // older rows where approved_by was never recorded).
                    'peopleSalaryIncrementStatusFinance:id,people_salary_increment_id,status,remarks,approved_by,action_date,modified_by',
                    'peopleSalaryIncrementStatusFinance.approver:id,Admin_Parent_id',
                    'peopleSalaryIncrementStatusFinance.approver.resortAdmin:id,first_name,last_name',
                    'peopleSalaryIncrementStatusGM:id,people_salary_increment_id,status,remarks,approved_by,action_date,modified_by',
                    'peopleSalaryIncrementStatusGM.approver:id,Admin_Parent_id',
                    'peopleSalaryIncrementStatusGM.approver.resortAdmin:id,first_name,last_name',
                ])->latest()
                ->get();

            if($query->count() > 0) {
                $downloadBtn = true;
            }

            // Footer visibility: show Approve / Reject / Request Change
            // for any row that's Pending OR Hold (held rows can still be
            // approved/rejected — Hold is a pause, not a lock for those
            // actions). `$showHoldButton` toggles the On Hold button
            // separately so we don't offer "Hold" on rows that are
            // already on hold.
            if($isFinanceOrDelegate){
                $peopleSalaryIncrementStatusFinance = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)->where('approval_rank', 'Finance')->whereIn('status', ['Pending','Hold'])->get();
                if($peopleSalaryIncrementStatusFinance->count() > 0){
                    $hasFinanceApproval = true;
                }
                $pendingFinanceCount = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)
                    ->where('approval_rank', 'Finance')->where('status', 'Pending')->count();
                if ($pendingFinanceCount > 0) {
                    $showHoldButton = true;
                }
            }elseif($isGMOrDelegate){
                $peopleSalaryIncrementStatusFinanceIds = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)->where('approval_rank', 'Finance')->where('status', 'Approved')->get();
                if($peopleSalaryIncrementStatusFinanceIds ->count() > 0){

                    $peopleSalaryIncrementStatusGM = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)->where('approval_rank', 'GM')->whereIn('status', ['Pending','Hold'])->get();
                    if($peopleSalaryIncrementStatusGM->count() > 0){
                        $hasGMApproval = true;
                    }
                    $pendingGmCount = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)
                        ->where('approval_rank', 'GM')->where('status', 'Pending')->count();
                    if ($pendingGmCount > 0) {
                        $showHoldButton = true;
                    }
                }
            }

            $currentBasicSalary = (clone $query)->sum('previous_salary');
            $newBasicSalary = (clone $query)->sum('new_salary');
            $monthlyPayrollIncrease = $newBasicSalary - $currentBasicSalary;
            $annualPayrollIncrease = $monthlyPayrollIncrease * 12;
            
             if($request->ajax())
                {
                    return datatables()->of($query)
                        ->addColumn('Emp_id', function($row){
                            return optional($row->employee)->Emp_id ?? '-';
                        })
                        ->addColumn('employee_name', function($row){
                            return optional(optional($row->employee)->resortAdmin)->full_name ?? '-';
                        })
                        ->addColumn('position_title', function($row){
                            return optional(optional($row->employee)->position)->position_title ?? '-';
                        })
                        ->addColumn('department_name', function($row){
                            return optional(optional($row->employee)->department)->name ?? '-';
                        })
                        ->editColumn('effective_date', function($query){
                            return $query->effective_date ? Carbon::parse($query->effective_date)->format('d M Y') : '-';
                        })
                        // Status badge + hold reason + due date. Pulls
                        // remarks from the matching per-stage row (Finance
                        // first, GM as fallback) since the hold reason is
                        // stored on PeopleSalaryIncrementStatus, NOT on
                        // PeopleSalaryIncrement (which only carries the
                        // ORIGINAL increment remark).
                        ->addColumn('status_info', function($row){
                            $status = trim((string) $row->status);
                            $class  = match ($status) {
                                'Approved'        => 'badge-themeSuccess',
                                'Rejected'        => 'badge-themeDanger',
                                'Hold'            => 'badge-themeSkyblue',
                                'Change-Request'  => 'badge-themeSkyblue',
                                default           => 'badge-themeWarning',
                            };
                            $html = '<span class="badge ' . $class . '" style="font-size:11px !important; font-weight:500 !important; padding:2px 8px !important;">'
                                . e($status ?: '-')
                                . '</span>';

                            // "Acted by" label per status — same label HR sees
                            // in the bell notifications. Pick the per-stage row
                            // whose status matches the overall status so the
                            // actor reflects the most recent transition.
                            $actorLabelMap = [
                                'Approved'       => 'Approved By',
                                'Rejected'       => 'Rejected By',
                                'Hold'           => 'Hold By',
                                'Change-Request' => 'Change Requested By',
                            ];
                            $stageRow = null;
                            foreach (['peopleSalaryIncrementStatusFinance','peopleSalaryIncrementStatusGM'] as $rel) {
                                $candidate = $row->{$rel};
                                if ($candidate && trim((string) $candidate->status) === $status) {
                                    $stageRow = $candidate;
                                    break;
                                }
                            }
                            // NOTE: This page is wrapped in
                            // `.card-salaryIncrementSum`, and default.css
                            // forces `.card-salaryIncrementSum strong
                            // { font-size: 26px }`. Use <span> with
                            // explicit weight instead of <strong> so
                            // the labels don't blow up to 26px.
                            //
                            // Resolve actor name with a fallback chain:
                            //  1. approved_by  → Employee → resortAdmin (set by
                            //     every transition going forward — the proper
                            //     audit field).
                            //  2. modified_by  → ResortAdmin (auto-set by the
                            //     model's save hook on EVERY update; rescues
                            //     historical Hold/Change-Request rows where
                            //     approved_by was never populated).
                            $actorName = optional(optional($stageRow)->approver?->resortAdmin)->full_name;
                            if (empty($actorName) && $stageRow && !empty($stageRow->modified_by)) {
                                // Cache per request so multiple Hold rows
                                // pointing at the same admin don't each
                                // re-query.
                                static $adminNameCache = [];
                                $mid = (int) $stageRow->modified_by;
                                if (!array_key_exists($mid, $adminNameCache)) {
                                    $ra = \App\Models\ResortAdmin::select('id','first_name','last_name')->find($mid);
                                    $adminNameCache[$mid] = $ra
                                        ? trim(($ra->first_name ?? '') . ' ' . ($ra->last_name ?? ''))
                                        : null;
                                }
                                $actorName = $adminNameCache[$mid];
                            }
                            if (!empty($actorName) && isset($actorLabelMap[$status])) {
                                $stageLabel = $stageRow && $stageRow->people_salary_increment_id
                                    ? ($row->peopleSalaryIncrementStatusFinance && $stageRow->id === $row->peopleSalaryIncrementStatusFinance->id ? 'Finance' : 'GM')
                                    : '';
                                $when = !empty($stageRow->action_date)
                                    ? ' on ' . e(Carbon::parse($stageRow->action_date)->format('d M Y'))
                                    : '';
                                $html .= '<div style="font-size:11px !important; line-height:1.3 !important; margin-top:3px; color:#555;">'
                                      . '<span style="font-weight:600; font-size:11px !important;">' . $actorLabelMap[$status] . ':</span> '
                                      . '<span style="font-size:11px !important;">' . e($actorName)
                                      . ($stageLabel ? ' (' . $stageLabel . ')' : '')
                                      . $when
                                      . '</span>'
                                      . '</div>';
                            }

                            if ($status === 'Hold' || $status === 'Change-Request') {
                                $reason = optional($row->peopleSalaryIncrementStatusFinance)->remarks
                                       ?: optional($row->peopleSalaryIncrementStatusGM)->remarks;
                                if (!empty($reason)) {
                                    $html .= '<div style="font-size:11px !important; line-height:1.3 !important; color:#555;">'
                                          . '<span style="font-weight:600; font-size:11px !important;">Reason:</span> '
                                          . '<span style="font-size:11px !important;">' . e($reason) . '</span>'
                                          . '</div>';
                                }
                                if (!empty($row->due_date)) {
                                    $html .= '<div style="font-size:11px !important; line-height:1.3 !important; color:#777;">'
                                          . '<span style="font-weight:600; font-size:11px !important;">Hold Until:</span> '
                                          . '<span style="font-size:11px !important;">' . e(Carbon::parse($row->due_date)->format('d M Y')) . '</span>'
                                          . '</div>';
                                }
                            }
                            return $html;
                        })
                        // Per-stage breakdown — "Finance: Pending / GM: Pending"
                        // — same render the /list page already shows. User
                        // wants this column on summary-list too so they don't
                        // have to switch pages to see chain progress.
                        ->addColumn('last_activity', function($row){
                            $badgeFor = function (string $status): string {
                                return match (trim($status)) {
                                    'Approved'       => 'badge-themeSuccess',
                                    'Rejected'       => 'badge-themeDanger',
                                    'Hold'           => 'badge-themeSkyblue',
                                    'On Hold'        => 'badge-themeSkyblue',
                                    'Change-Request' => 'badge-themeSkyblue',
                                    default          => 'badge-themeWarning',
                                };
                            };
                            // Same .card-salaryIncrementSum strong{font-size:26px}
                            // hijack as elsewhere on this page — use <span>
                            // with explicit weight/size + !important on
                            // every inline rule.
                            $stageRow = function (string $label, $stage) use ($badgeFor) {
                                if (!$stage) return '';
                                $status  = e((string) $stage->status);
                                $remarks = !empty($stage->remarks) ? ' &mdash; ' . e($stage->remarks) : '';
                                return '<div class="mb-1" style="font-size:12px !important; line-height:1.3 !important;">'
                                    . '<span style="font-weight:600; font-size:12px !important;">' . e($label) . ':</span> '
                                    . '<span class="badge ' . $badgeFor($stage->status) . '" style="font-size:11px !important; font-weight:500 !important; padding:2px 6px !important;">' . $status . '</span>'
                                    . '<span style="font-size:12px !important;">' . $remarks . '</span>'
                                    . '</div>';
                            };
                            return $stageRow('Finance', $row->peopleSalaryIncrementStatusFinance)
                                 . $stageRow('GM',      $row->peopleSalaryIncrementStatusGM);
                        })
                        ->with([
                            'currentBasicSalary' => $currentBasicSalary,
                            'newBasicSalary' => $newBasicSalary,
                            'monthlyPayrollIncrease' => $monthlyPayrollIncrease,
                            'annualPayrollIncrease' => $annualPayrollIncrease
                        ])
                        ->rawColumns(['Emp_id','department_name','employee_name','position_title','status_info','last_activity'])
                        ->make(true);
                }
        
        return view('resorts.people.salary-increment-summary.list', compact('page_title','hasGMApproval','hasFinanceApproval','downloadBtn','showHoldButton'));
    }
    
    // update status of salary increment
    public function updateStatus(Request $request){

        $status = $request->status;
        $paylaod = is_string($request->payload) ? json_decode($request->payload, true) : $request->payload;

        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        
        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
            
        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

       
        $gmApprover = Employee::with('position')
            ->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();
        
        // Add approvers to each incrementData array
        if (is_array($paylaod)) {
            foreach ($paylaod as &$incrementData) {
            if ($financeApprover) {
                $incrementData['approver'] = $financeApprover;
                $incrementData['approval_rank'] = 'Finance';
            }
            if ($gmApprover) {
                $incrementData['approver'] = $gmApprover;
                $incrementData['approval_rank'] = 'GM';
            }
            }
            unset($incrementData);
        }
        
        if (is_array($paylaod)) {
            foreach ($paylaod as $incrementData) {
                // Approver identity above is correctly scoped to this
                // resort, but this was PeopleSalaryIncrement::find($id) with
                // no resort filter on the TARGET row — the most severe gap
                // in this flow: a foreign resort's increment id, once
                // GM-approved here, would write the new salary onto that
                // foreign employee's real basic_salary. Scope before any
                // approver-identity/status logic runs.
                $increment = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->find($incrementData['id']);

                if ($increment) {
                    $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id);
                    if($incrementData['approval_rank']){
                                $update_key = false;

                        if($incrementData['approval_rank'] == 'Finance') {
                                $update_key = true;

                            // Hold is a pause, not a lock — approver can still
                            // Approve/Reject a held row (matches promotion
                            // semantics at PromotionController:737). Filter
                            // must accept BOTH 'Pending' and 'Hold', otherwise
                            // the chained where() narrows the update target to
                            // zero rows and the action silently fails.
                            $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                            ->whereIn('status', ['Pending','Hold'])->first();

                        }elseif($incrementData['approval_rank'] == 'GM') {
                            $peopleSalaryIncrementStatusFinance =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'Finance')->where('status', 'Approved')->first();

                            if($peopleSalaryIncrementStatusFinance){
                                $update_key = true;
                                $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                                ->whereIn('status', ['Pending','Hold'])->first();
                            }else{
                                return response()->json([
                                        'success' => false,
                                        'status' => 'Error',
                                        'message' => 'Action can be taken after finance aprroval.'
                                    ]);
                            }
                        }
                        
                        if ($update_key == true) {
                            $peopleSalaryIncrementStatus->update([
                                'status' => $status,
                                'approved_by' => $incrementData['approver']->id,
                                'action_date' => now(),
                                'remarks' => $request->remarks,
                                'reject_reason' => $request->rejected_reason,
                            ]);


                            if($status == 'Rejected' || $status == 'Change-Request'){
                                $increment->update([
                                    'status' => $status,
                                ]);
                            }

                            // Fan-out notifications for this transition.
                            // Reject reason carries more weight than remarks,
                            // so prefer it when present.
                            $this->dispatchActionNotifications(
                                $increment->fresh(),
                                $status,
                                $incrementData['approval_rank'],
                                $request->rejected_reason ?: $request->remarks
                            );
                        }
    
                        $peopleSalaryIncrementStatusGm =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'GM')->where('status', '!=','Pending')->first();

                        if($peopleSalaryIncrementStatusGm){
                            $increment->update([
                                'status' => $peopleSalaryIncrementStatusGm->status,
                            ]);

                            // Apply the salary change to the employee ONLY when:
                            //   (1) GM marked it Approved (not Rejected/etc), AND
                            //   (2) the effective_date is today or earlier.
                            // For future-dated increments we leave the employee
                            // row alone — the daily scheduler command
                            // `salary-increment:apply-effective` picks the row
                            // up on the effective date, applies the change,
                            // and stamps `effective_day_applied_at` to keep
                            // it idempotent (mirrors the promotion module).
                            $isApproved = $peopleSalaryIncrementStatusGm->status === 'Approved';
                            $effectiveToday = !empty($increment->effective_date)
                                && \Carbon\Carbon::parse($increment->effective_date)->toDateString() <= \Carbon\Carbon::today()->toDateString();
                            if (!($isApproved && $effectiveToday)) {
                                // Skip the immediate-apply block — either the
                                // GM action wasn't an approval, or the
                                // increment is future-dated and waits for
                                // its day-of run.
                                continue;
                            }

                            // Reuse the shared apply helper so updateStatus
                            // and the daily catch-up command write through
                            // exactly the same fields and stamp the
                            // idempotency timestamp.
                            self::applyApprovedIncrementToEmployee($increment->fresh());
                        }
                    }else{
                        return response()->json([
                                'success' => false,
                                'status' => 'Error',
                                'message' => 'Unauthorized action.'
                            ]);
                    }
                }
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Data updated successfully.',
            'redirect_url' => route('people.salary-increment.summary-list'),
        ]);
    }
    

    public function requestChange(Request $request){
        $paylaod = is_string($request->payload) ? json_decode($request->payload, true) : $request->payload;
         $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
            
        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

       
        $gmApprover = Employee::with('position')
            ->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();
        
        // Add approvers to each incrementData array
        if (is_array($paylaod)) {
            foreach ($paylaod as &$incrementData) {
            if ($financeApprover) {
                $incrementData['approver'] = $financeApprover;
                $incrementData['approval_rank'] = 'Finance';
            }
            if ($gmApprover) {
                $incrementData['approver'] = $gmApprover;
                $incrementData['approval_rank'] = 'GM';
            }
            }
            unset($incrementData);
        }

        if (is_array($paylaod)) {
            foreach ($paylaod as $incrementData) {

                // Was ->find($id) with no resort filter — unscoped write
                // let another resort's increment row be pushed to
                // Change-Request.
                $increment = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->find($incrementData['id']);

                if ($increment) {
                   $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id);
                    if($incrementData['approval_rank']){
                                $update_key = false;

                        if($incrementData['approval_rank'] == 'Finance') {
                                $update_key = true;

                            $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                            ->where('status', 'Pending')->first();
    
                        }elseif($incrementData['approval_rank'] == 'GM') {
                            $peopleSalaryIncrementStatusFinance =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'Finance')->where('status', 'Approved')->first();
    
                            if($peopleSalaryIncrementStatusFinance){
                                $update_key = true;
                                $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                                ->where('status', 'Pending')->first();
                            }
                        }
                        
                        if ($update_key == true) {
                            $peopleSalaryIncrementStatus->update([
                                'status' => 'Change-Request',
                                // Track who raised the change request so
                                // the summary-list can show
                                // "Change Requested By: <name>".
                                'approved_by' => optional($incrementData['approver'] ?? null)->id,
                                'action_date' => now(),
                                'remarks' => $request->remarks,
                            ]);

                            $increment->update([
                                'status' => 'Change-Request',
                            ]);

                            // Notify HR so they can edit + re-submit.
                            $this->dispatchActionNotifications(
                                $increment->fresh(),
                                'Change-Request',
                                $incrementData['approval_rank'],
                                $request->remarks
                            );
                        }
                    }
                }
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Data updated successfully.',
            'redirect_url' => route('people.salary-increment.summary-list'),
        ]);
    }

    public function holdRequest(Request $request){

        // Both fields are required — empty due_date used to crash with
        // "Not enough data available to satisfy format" because the parser
        // ran on '' before any guard. Validate first, then parse.
        $request->validate([
            'remarks'  => 'required|string',
            'due_date' => 'required|string',
        ]);

        $paylaod = is_string($request->payload) ? json_decode($request->payload, true) : $request->payload;
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        
        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
            
        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

       
        $gmApprover = Employee::with('position')
            ->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

            if ($financeApprover) {
                $approver = $financeApprover;
                $approval_rank = 'Finance';
            }else{

                $approver = $gmApprover;
                $approval_rank = 'GM';
            }
        
            // Accept either the "d/m/Y" format the date-picker emits or a
            // raw "Y-m-d" string (some browsers/extensions submit the
            // native format). Either way we end up with a clean Y-m-d.
            try {
                $dueDate = Carbon::createFromFormat('d/m/Y', (string) $request->due_date)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dueDate = Carbon::parse((string) $request->due_date)->format('Y-m-d');
            }

        if (is_array($paylaod)) {
            
            foreach ($paylaod as $incrementData) {

                // Was ->find($id) with no resort filter — unscoped write
                // let another resort's increment row be put on Hold.
                $increment = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->find($incrementData['id']);

                if ($increment) {
                   $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id);
                    if($approval_rank){
                        $update_key = false;

                        if($approval_rank == 'Finance') {
                            $update_key = true;
                            $peopleSalaryIncrementStatus->where('approval_rank', $approval_rank)
                            ->where('status', 'Pending')->first();
                            
                        }elseif($approval_rank == 'GM') {
                            $peopleSalaryIncrementStatusFinance =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'Finance')->where('status', 'Approved')->first();
    
                            if($peopleSalaryIncrementStatusFinance){
                                $update_key = true;
                                $peopleSalaryIncrementStatus->where('approval_rank', $approval_rank)
                                ->where('status', 'Pending')->first();
                            }
                        }
                        
                        if ($update_key == true) {
                            $peopleSalaryIncrementStatus->update([
                                'status' => 'Hold',
                                // Track who put it on hold so the
                                // summary-list can show "Hold By: <name>".
                                // approved_by is the generic "actor" column
                                // shared by every transition (named for the
                                // original approve flow).
                                'approved_by' => optional($approver)->id,
                                'action_date' => now(),
                                'remarks' => $request->remarks,
                            ]);

                            $increment->update([
                                'status' => 'Hold',
                                'due_date' => $dueDate,
                            ]);

                            // Notify HR + the affected employee — Hold is a
                            // visible state change they both need to know
                            // about (HR can't act on the row, employee
                            // sees their pay change paused).
                            $this->dispatchActionNotifications(
                                $increment->fresh(),
                                'Hold',
                                $approval_rank,
                                $request->remarks
                            );
                        }
                    }
                }
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Hold Request successfully.',
            'redirect_url' => route('people.salary-increment.summary-list'),
        ]);
    }

    public function downloadByFormate(Request $request)
    {
        if($request->file == 'excel'){
            return Excel::download(new SalaryIncrementExport, 'salary_increment_summary.xlsx'); 
        
        }else{
            $data = PeopleSalaryIncrement::where('resort_id',$this->resort->resort_id)->select('id', 'employee_id', 'increment_type', 'effective_date', 'value', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status')
                    ->with([
                        'employee.resortAdmin:id,first_name,last_name', 
                        'employee.department:id,name',      
                        'employee.position:id,position_title',
                        'peopleSalaryIncrementStatusFinance',
                        'peopleSalaryIncrementStatusGM'
                    ])
                    ->whereHas('employee', function ($q) {
                        $q->where('resort_id', $this->resort->resort_id);
                    })->get();

                $currentBasicSalary = (clone $data)->sum('previous_salary');
                $newBasicSalary = (clone $data)->sum('new_salary');
                $monthlyPayrollIncrease = $newBasicSalary - $currentBasicSalary;
                $annualPayrollIncrease = $monthlyPayrollIncrease * 12;

            $pdf = \PDF::loadView('resorts.people.salary-increment-summary.pdf', compact('data','currentBasicSalary','newBasicSalary','monthlyPayrollIncrease','annualPayrollIncrease'));
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('salary_increment_summary.pdf');
        }
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'try agin successfully.'
        ]);
    }

    public function incrementHistory (Request $request){

        if(Common::checkRouteWisePermission('people.salary-increment.summary-list',config('settings.resort_permissions.view')) == false && Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title = 'Salary Increment History';
        $employeeId = base64_decode($request->id);

        // History scoping — terminal-state only:
        //  • Default (HR / non-approvers): rows where the OVERALL status is
        //    Approved or Rejected.
        //  • Finance approver (or delegate): ALSO show rows where their own
        //    Finance stage is Approved/Rejected, even if the overall row is
        //    still 'Pending' waiting on GM. Without this, after Finance
        //    approves they lose track of what they signed off — the row
        //    disappears from the summary-list (correctly — they have no
        //    pending action) but doesn't yet show in history either.
        //  • GM approver (or delegate): same idea for the GM stage.
        $resortId = $this->resort->resort_id;
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];
        $financePositionIds = ResortPosition::where('resort_id', $resortId)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
        $financeApprover = Employee::whereIn('position_id', $financePositionIds)
            ->where('resort_id', $resortId)
            ->where('Admin_Parent_id', $this->resort->id)
            ->select('id')->first();
        $gmApprover = Employee::where('rank', 8)
            ->where('resort_id', $resortId)
            ->where('Admin_Parent_id', $this->resort->id)
            ->select('id')->first();
        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        $isFinanceOrDelegate = $financeApprover
            && ($financeApprover->id == $currentEmpId
                || \App\Helpers\Common::hasDelegationAuthority($currentEmpId, $financeApprover->id, $resortId));
        $isGMOrDelegate = $gmApprover
            && ($gmApprover->id == $currentEmpId
                || \App\Helpers\Common::hasDelegationAuthority($currentEmpId, $gmApprover->id, $resortId));

        $ids = PeopleSalaryIncrement::where('resort_id', $resortId)
            ->where(function ($q) use ($isFinanceOrDelegate, $isGMOrDelegate) {
                $q->whereIn('status', ['Approved', 'Rejected']);
                if ($isFinanceOrDelegate) {
                    $q->orWhereHas('peopleSalaryIncrementStatusFinance', function ($q2) {
                        $q2->whereIn('status', ['Approved', 'Rejected']);
                    });
                }
                if ($isGMOrDelegate) {
                    $q->orWhereHas('peopleSalaryIncrementStatusGM', function ($q2) {
                        $q2->whereIn('status', ['Approved', 'Rejected']);
                    });
                }
            })
            ->pluck('id');

        // Note: the legacy where('status', IN [Approved, Rejected]) filter
        // below has been removed — the per-role $ids set now drives the
        // visible set, so a "Pending overall / Finance-Approved" row can
        // appear in Finance's history without being filtered back out.
        $query = PeopleSalaryIncrement::whereIn('id', $ids)
            ->select('id', 'employee_id', 'increment_type', 'effective_date', 'value', 'pay_increase_type', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status','created_at')
            ->with([
                'employee.resortAdmin:id,first_name,last_name',
                'employee.department:id,name',
                'employee.position:id,position_title',
                // Per-stage rows carry the reject_reason, remarks, AND the
                // actor (via approved_by / modified_by). Pull approver →
                // Employee → resortAdmin too so we can resolve the actor's
                // name and append "by <Actor>" on Rejected rows.
                'peopleSalaryIncrementStatusFinance:id,people_salary_increment_id,status,remarks,reject_reason,approved_by,action_date,modified_by',
                'peopleSalaryIncrementStatusFinance.approver:id,Admin_Parent_id',
                'peopleSalaryIncrementStatusFinance.approver.resortAdmin:id,first_name,last_name',
                'peopleSalaryIncrementStatusGM:id,people_salary_increment_id,status,remarks,reject_reason,approved_by,action_date,modified_by',
                'peopleSalaryIncrementStatusGM.approver:id,Admin_Parent_id',
                'peopleSalaryIncrementStatusGM.approver.resortAdmin:id,first_name,last_name',
            ])
            ->get();

       
            if($request->ajax())
            {
                return datatables()->of($query)
                    ->addColumn('Emp_id', function($row){
                        return optional($row->employee)->Emp_id ?? '-';
                    })
                    ->addColumn('employee_name', function($row){
                        return optional(optional($row->employee)->resortAdmin)->full_name ?? '-';
                    })
                    ->addColumn('position_title', function($row){
                        return optional(optional($row->employee)->position)->position_title ?? '-';
                    })
                    ->addColumn('department_name', function($row){
                        return optional(optional($row->employee)->department)->name ?? '-';
                    })
                    ->editColumn('effective_date', function($query){
                        return $query->effective_date ? Carbon::parse($query->effective_date)->format('d M Y') : '-';
                    })
                    // Render the overall status as a coloured badge — same
                    // theme classes the list page uses for the per-stage
                    // badges. Without this the column rendered the raw
                    // "Pending" / "Approved" text with no colour at all.
                    ->editColumn('status', function($row){
                        $status = trim((string) $row->status);
                        $class  = match ($status) {
                            'Approved'        => 'badge-themeSuccess',
                            'Rejected'        => 'badge-themeDanger',
                            'On Hold'         => 'badge-themeSkyblue',
                            'Change-Request'  => 'badge-themeSkyblue',
                            default           => 'badge-themeWarning',
                        };
                        $html = '<span class="badge ' . $class . '" style="font-size:11px !important; font-weight:500 !important; padding:2px 8px !important;">'
                            . e($status ?: '-')
                            . '</span>';

                        // Append "Rejected by <Stage>: <reason>" + actor
                        // name on Rejected rows. The reject_reason lives
                        // on PeopleSalaryIncrementStatus of whichever
                        // stage rejected (Finance OR GM); the actor name
                        // is resolved via approved_by → Employee →
                        // resortAdmin with a modified_by fallback for
                        // older rows that never set approved_by.
                        if ($status === 'Rejected') {
                            $finance = $row->peopleSalaryIncrementStatusFinance;
                            $gm      = $row->peopleSalaryIncrementStatusGM;
                            $stageRow = null;
                            $stageLabel = null;
                            if ($finance && trim((string) $finance->status) === 'Rejected') {
                                $stageRow   = $finance;
                                $stageLabel = 'Finance';
                            } elseif ($gm && trim((string) $gm->status) === 'Rejected') {
                                $stageRow   = $gm;
                                $stageLabel = 'GM';
                            }
                            if ($stageRow) {
                                // Resolve actor name with the same fallback
                                // chain used on the summary-list page:
                                //  1. approved_by → Employee → resortAdmin
                                //  2. modified_by → ResortAdmin (model
                                //     boot-hook auto-sets it on every
                                //     update — rescues historical rows).
                                $actorName = optional(optional($stageRow)->approver?->resortAdmin)->full_name;
                                if (empty($actorName) && !empty($stageRow->modified_by)) {
                                    static $adminNameCache = [];
                                    $mid = (int) $stageRow->modified_by;
                                    if (!array_key_exists($mid, $adminNameCache)) {
                                        $ra = \App\Models\ResortAdmin::select('id','first_name','last_name')->find($mid);
                                        $adminNameCache[$mid] = $ra
                                            ? trim(($ra->first_name ?? '') . ' ' . ($ra->last_name ?? ''))
                                            : null;
                                    }
                                    $actorName = $adminNameCache[$mid];
                                }
                                $reason = $stageRow->reject_reason ?: $stageRow->remarks;
                                $when   = !empty($stageRow->action_date)
                                    ? ' on ' . e(Carbon::parse($stageRow->action_date)->format('d M Y'))
                                    : '';
                                // Line 1 — who and when.
                                $html .= '<div style="font-size:11px !important; line-height:1.3 !important; margin-top:3px; color:#555;">'
                                    . '<span style="font-weight:600; font-size:11px !important;">Rejected by '
                                    . e($stageLabel) . ':</span> '
                                    . '<span style="font-size:11px !important;">'
                                    . e($actorName ?: '—') . $when
                                    . '</span>'
                                    . '</div>';
                                // Line 2 — reason text, only when present.
                                if (!empty($reason)) {
                                    $html .= '<div style="font-size:11px !important; line-height:1.3 !important; color:#555;">'
                                        . '<span style="font-weight:600; font-size:11px !important;">Reason:</span> '
                                        . '<span style="font-size:11px !important;">' . e($reason) . '</span>'
                                        . '</div>';
                                }
                            }
                        }
                        return $html;
                    })

                    ->rawColumns(['Emp_id','department_name','employee_name','position_title','status'])
                    ->make(true);
            }
        return view('resorts.people.salary-increment-summary.increment-history', compact('page_title'));

    }
}

