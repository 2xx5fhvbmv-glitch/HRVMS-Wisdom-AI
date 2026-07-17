<?php

namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use App\Models\LearningCategory;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Models\LearningRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\ResortDepartment;
use App\Models\TrainingSchedule;
use App\Models\TrainingAttendance;
use App\Models\AttendanceParameters;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
  
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }
    
    public function HR_Dashobard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;

        // HR (and HR-dept HOD/EXCOM) is now scoped to their own department.
        // Counts and lists below filter to schedules / requests that involve at
        // least one in-scope employee, mirroring the HOD dashboard's pattern.
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();
        $today = \Carbon\Carbon::now()->toDateString();

        // Date-derived counts (status field is rarely advanced manually). See
        // manager_dashboard() for the same scheme.
        $countByDateRule = function ($builderTweak) use ($resort_id, $scopedEmpIds) {
            $q = TrainingSchedule::where('resort_id', $resort_id);
            $builderTweak($q);
            if (is_array($scopedEmpIds)) {
                $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds));
            }
            return $q->count();
        };
        // Pure date-derived counts so they exactly match the schedule list filters
        // (raw `status` column is unreliable — every row defaults to 'Scheduled').
        $ongoing_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('start_date', '<=', $today)->where('end_date', '>=', $today);
        });
        $completed_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('end_date', '<', $today);
        });
        $scheduled_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('start_date', '>', $today);
        });

        $pending_trainings_count = LearningRequest::where('status', 'Pending')
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('employees', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->count();

        $pending_learning_request = LearningRequest::with('learning')
            ->where('status', 'Pending')
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('employees', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->get();

        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances','participants'])
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        $trainingSchedulesQuery = TrainingSchedule::with('participants.employee', 'trainingAttendances')
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)));
        $trainingSchedules = $trainingSchedulesQuery->get();

        // Define the threshold for completion (e.g., 70%)
        $AttendanceParameters = AttendanceParameters::where('resort_id',$resort_id)->first();
           
   
        $completionThreshold = $AttendanceParameters['threshold_percentage'] ?? 0;
       $completionData = [];

        // Iterate over each training schedule
        foreach ($trainingSchedules as $schedule) {
            $totalParticipants = $schedule->participants->count();
            $completedParticipants = 0;

            // Iterate over each participant
            foreach ($schedule->participants as $participant) {
                // Count total sessions and attended sessions
                $totalSessions = $schedule->trainingAttendances->count();
                $attendedSessions = $schedule->trainingAttendances->where('employee_id', $participant->employee_id)->count();

                // Calculate attendance percentage
                $attendancePercentage = ($totalSessions > 0) ? ($attendedSessions / $totalSessions) * 100 : 0;

                // Check if attendance meets or exceeds the threshold
                if ($attendancePercentage >= $completionThreshold) {
                    $completedParticipants++;
                }
            }

            // Calculate completion rate for the training
            $completionRate = ($totalParticipants > 0) ? ($completedParticipants / $totalParticipants) * 100 : 0;

            // Store the data
            $completionData[] = [
                'training_name' => $schedule->learningProgram->name ?? 'Unnamed Program',
                'completion_rate' => $completionRate,
                'total_participants' => $totalParticipants,
                'completed_participants' => $completedParticipants,
            ];
        }
        // dd($completionData);
        // Dynamic dashboard widgets — replace hardcoded "70%" / "Learning 1" placeholders.
        $feedbackAvgScore     = $this->computeAverageEvaluationScore($resort_id);
        $onboardingProgress   = $this->computeCompulsoryCompletionPercent($resort_id, null);
        $learningHoursByProg  = $this->computeLearningHoursByProgram($resort_id);
        $learningAttendance   = $this->computeLearningAttendanceBreakdown($resort_id);

        $learningInsights     = $this->getCachedLearningInsights($resort_id);

        return view('resorts.learning.dashboard.hrdashboard',compact('page_title','scheduled_trainings_count','pending_trainings_count','completed_trainings_count','pending_learning_request','trainings','completionData','feedbackAvgScore','onboardingProgress','learningHoursByProg','learningAttendance','learningInsights'));
    }

    /**
     * Cached wrapper around buildLearningInsights() with a manual 2-day refresh,
     * mirroring the TA/Leave/Payroll dashboards. Cached per resort; the
     * "Regenerate" link loads ?regenerate_insights=1 and recomputes only once the
     * 48h cooldown has elapsed. The returned array carries a '_meta' key.
     */
    private function getCachedLearningInsights($resortId): array
    {
        $cooldownHours = 48;
        $cacheKey = 'learning_insights:' . $resortId;
        $now = \Carbon\Carbon::now();

        $cached = \Cache::get($cacheKey);
        $regenerate = request()->boolean('regenerate_insights');

        $stale = !is_array($cached) || empty($cached['generated_at']);
        if (!$stale) {
            $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
            if ($regenerate && $generatedAt->diffInHours($now) >= $cooldownHours) {
                $stale = true;
            }
        }

        if ($stale) {
            $cached = [
                'insights'     => $this->buildLearningInsights($resortId),
                'generated_at' => $now->toIso8601String(),
            ];
            \Cache::put($cacheKey, $cached, $now->copy()->addDays(30));
        }

        $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
        $insights = $cached['insights'];
        $insights['_meta'] = [
            'generated_at'   => $generatedAt,
            'can_regenerate' => $generatedAt->diffInHours($now) >= $cooldownHours,
            'next_available' => $generatedAt->copy()->addHours($cooldownHours),
        ];

        return $insights;
    }

    /**
     * Compute the four Learning & Development AI-insight cards for a resort:
     *   1. completion    Training completion rate vs the attendance threshold
     *   2. mandatory     Mandatory-program compliance across targeted staff
     *   3. requests      Learning-request pipeline (status mix, categories, denials)
     *   4. probationary  Probationary new-hire required-training completion
     * Each card is wrapped so one failing query degrades just that card. The
     * deterministic numbers are then narrated by the FastAPI LLM (best-effort).
     */
    private function buildLearningInsights($resortId): array
    {
        $insights = [
            'completion'   => ['title' => 'Training Completion Rate',     'body' => 'No training attendance recorded yet to measure completion.'],
            'mandatory'    => ['title' => 'Mandatory Training Compliance', 'body' => 'No mandatory programs configured yet.'],
            'requests'     => ['title' => 'Learning Request Pipeline',     'body' => 'No learning requests submitted yet.'],
            'probationary' => ['title' => 'Probationary Training Progress', 'body' => 'No probationary programs or staff on probation yet.'],
        ];

        // --- Card 1: Training completion rate (mirrors the page's completionData) ---
        try {
            $threshold = (float) (AttendanceParameters::where('resort_id', $resortId)->value('threshold_percentage') ?? 0);
            $schedules = TrainingSchedule::with(['learningProgram', 'trainingAttendances', 'participants'])
                ->where('resort_id', $resortId)->get();
            $rows = []; $sumParticipants = 0; $sumCompleted = 0;
            foreach ($schedules as $s) {
                $total = $s->participants->count();
                if ($total === 0) continue;
                $totalSessions = $s->trainingAttendances->count();
                $done = 0;
                foreach ($s->participants as $p) {
                    $attended = $s->trainingAttendances->where('employee_id', $p->employee_id)->count();
                    $pct = $totalSessions > 0 ? ($attended / $totalSessions) * 100 : 0;
                    if ($pct >= $threshold) $done++;
                }
                $sumParticipants += $total; $sumCompleted += $done;
                $rows[] = [
                    'program'      => $s->learningProgram->name ?? 'Unnamed Program',
                    'participants' => $total,
                    'completed'    => $done,
                    'rate'         => round($total > 0 ? $done / $total * 100 : 0, 1),
                ];
            }
            if ($sumParticipants > 0) {
                $overall = round($sumCompleted / $sumParticipants * 100, 1);
                usort($rows, fn ($a, $b) => $a['rate'] <=> $b['rate']);
                $lowest = $rows[0];
                $insights['completion']['body'] = $overall . '% of ' . $sumParticipants . ' participants met the ' . (int) $threshold . '% attendance threshold across ' . count($rows) . ' programs; lowest is "' . $lowest['program'] . '" at ' . $lowest['rate'] . '%.';
                $insights['completion']['details'] = ['overall' => $overall, 'threshold' => (int) $threshold, 'rows' => array_slice($rows, 0, 15)];
            }
        } catch (\Throwable $e) {}

        // --- Card 2: Mandatory training compliance ------------------------------
        try {
            $mps = \DB::table('mandatory_learning_programs as m')
                ->leftJoin('learning_programs as p', 'p.id', '=', 'm.program_id')
                ->where('m.resort_id', $resortId)
                ->select('m.program_id', 'm.department_id', 'm.position_id', 'p.name')->get();
            $totReq = 0; $totDone = 0; $rows = [];
            foreach ($mps as $m) {
                $eligible = Employee::where('resort_id', $resortId)->where('status', 'Active');
                if ($m->department_id) $eligible->where('Dept_id', $m->department_id);
                if ($m->position_id)   $eligible->where('Position_id', $m->position_id);
                $empIds = $eligible->pluck('id');
                $req = $empIds->count();
                if ($req === 0) continue;
                $done = (int) \DB::table('training_attendance as ta')
                    ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                    ->where('ts.resort_id', $resortId)->where('ts.training_id', $m->program_id)
                    ->whereIn('ta.employee_id', $empIds)->where('ta.status', 'Present')
                    ->distinct()->count('ta.employee_id');
                $totReq += $req; $totDone += $done;
                $rows[] = ['program' => $m->name ?: 'Program', 'required' => $req, 'completed' => $done, 'pct' => round($req > 0 ? $done / $req * 100 : 0, 1)];
            }
            if ($totReq > 0) {
                $pct = round($totDone / $totReq * 100, 1);
                $outstanding = $totReq - $totDone;
                usort($rows, fn ($a, $b) => $a['pct'] <=> $b['pct']);
                $insights['mandatory']['body'] = $pct . '% mandatory-training compliance; ' . $outstanding . ' of ' . $totReq . ' required completions still outstanding across ' . count($rows) . ' programs.';
                $insights['mandatory']['details'] = ['pct' => $pct, 'required' => $totReq, 'completed' => $totDone, 'outstanding' => $outstanding, 'rows' => array_slice($rows, 0, 15)];
            }
        } catch (\Throwable $e) {}

        // --- Card 3: Learning request pipeline ----------------------------------
        try {
            $byStatus = LearningRequest::where('resort_id', $resortId)
                ->select('status', \DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');
            $total = (int) $byStatus->sum();
            if ($total > 0) {
                $pending  = (int) ($byStatus['Pending'] ?? 0);
                $approved = (int) ($byStatus['Approved'] ?? 0);
                $denied   = (int) ($byStatus['Denied'] ?? 0);
                $onHold   = (int) ($byStatus['On Hold'] ?? 0);
                $decided  = $approved + $denied;
                $approvalRate = $decided > 0 ? round($approved / $decided * 100, 1) : null;
                $byCat = \DB::table('learning_requests as lr')
                    ->join('learning_programs as p', 'p.id', '=', 'lr.learning_id')
                    ->leftJoin('learning_categories as c', 'c.id', '=', 'p.learning_category_id')
                    ->where('lr.resort_id', $resortId)
                    ->select(\DB::raw("COALESCE(c.name,'Uncategorised') as cat"), \DB::raw('COUNT(*) as c'))
                    ->groupBy('cat')->orderByDesc('c')->limit(10)->get()
                    ->map(fn ($r) => ['category' => $r->cat, 'count' => (int) $r->c])->all();
                $denials = \DB::table('learning_requests')->where('resort_id', $resortId)
                    ->where('status', 'Denied')->whereNotNull('rejection_reason')->where('rejection_reason', '!=', '')
                    ->select('rejection_reason as reason', \DB::raw('COUNT(*) as c'))
                    ->groupBy('rejection_reason')->orderByDesc('c')->limit(10)->get()
                    ->map(fn ($r) => ['reason' => $r->reason, 'count' => (int) $r->c])->all();
                $rateTxt = $approvalRate === null ? 'no decisions yet' : $approvalRate . '% approval';
                $insights['requests']['body'] = $total . ' learning requests (' . $pending . ' pending, ' . $approved . ' approved, ' . $denied . ' denied, ' . $onHold . ' on hold); ' . $rateTxt . '.';
                $insights['requests']['details'] = [
                    'counts' => ['Pending' => $pending, 'Approved' => $approved, 'Denied' => $denied, 'On Hold' => $onHold],
                    'approval_rate' => $approvalRate, 'categories' => $byCat, 'denials' => $denials,
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 4: Probationary training progress -----------------------------
        try {
            $programIds = \App\Models\ProbationaryLearningProgram::where('resort_id', $resortId)->pluck('program_id');
            $probationary = Employee::where('resort_id', $resortId)->where('status', 'Active')
                ->where(function ($q) {
                    $q->where('employment_type', 'Probationary')->orWhereIn('probation_status', ['Active', 'Extended']);
                })->pluck('id');
            if ($programIds->isNotEmpty() && $probationary->isNotEmpty()) {
                $totalPairs = $probationary->count() * $programIds->count();
                $completedPairs = (int) \DB::table('training_attendance as ta')
                    ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                    ->where('ts.resort_id', $resortId)->whereIn('ts.training_id', $programIds)
                    ->whereIn('ta.employee_id', $probationary)->where('ta.status', 'Present')
                    ->distinct()->count(\DB::raw('CONCAT(ta.employee_id, "-", ts.training_id)'));
                $pct = $totalPairs > 0 ? round($completedPairs / $totalPairs * 100, 1) : 0;
                $outstanding = $totalPairs - $completedPairs;
                $insights['probationary']['body'] = $probationary->count() . ' staff on probation × ' . $programIds->count() . ' required programs: ' . $pct . '% complete, ' . $outstanding . ' of ' . $totalPairs . ' completions outstanding.';
                $insights['probationary']['details'] = [
                    'employees' => $probationary->count(), 'programs' => $programIds->count(),
                    'pct' => $pct, 'completed' => $completedPairs, 'total' => $totalPairs, 'outstanding' => $outstanding,
                ];
            }
        } catch (\Throwable $e) {}

        // Narrate the deterministic numbers via the FastAPI LLM (best-effort).
        $insights = Common::enrichDashboardInsights(
            $insights, 'learning & development', ['completion', 'mandatory', 'requests', 'probationary']
        );

        return $insights;
    }

    public function admin_dashboard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;
        $ongoing_trainings_count = TrainingSchedule::where('status','Ongoing')->where('resort_id', $resort_id)->count();
        $completed_trainings_count = TrainingSchedule::where('status','Completed')->where('resort_id', $resort_id)->count();
        $scheduled_trainings_count = TrainingSchedule::where('status','Scheduled')->where('resort_id', $resort_id)->count();
        $pending_trainings_count = LearningRequest::where('status','Pending')->where('resort_id', $resort_id)->count();
        $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->get();
        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances'])
        ->where('resort_id', $resort_id)
        ->orderBy('start_date', 'desc')
        ->limit(5)
        ->get();
        // dd( $pending_trainings_count);
        return view('resorts.learning.dashboard.admin-dashboard',compact('page_title','scheduled_trainings_count','pending_trainings_count','completed_trainings_count','pending_learning_request','trainings'));
    }

    public function hod_dashboard()
    {
        $page_title ='Learning';
        $dashboardLabel = request('dashboard_label', 'HOD');
        $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';
        $resort_id= $this->resort->resort_id;

        // Department-visibility scope: HR/GM see resort-wide; other HOD/XCOM only their own dept.
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $scopedEmpIds  = Common::getPerformanceScopedEmpIds();
        $today = \Carbon\Carbon::now()->toDateString();

        // Tile counts derive from dates (status field rarely advanced manually) AND
        // scope by participants — matching the schedule list. So a Chief Engineer
        // sees counts of every schedule he or his subordinates are enrolled in.
        $countByDateRule = function ($builderTweak) use ($resort_id, $scopedEmpIds) {
            $q = TrainingSchedule::where('resort_id', $resort_id);
            $builderTweak($q);
            if (is_array($scopedEmpIds)) {
                $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds));
            }
            return $q->count();
        };
        // Pure date-derived counts so they exactly match the schedule list filters
        // (raw `status` column is unreliable — every row defaults to 'Scheduled').
        $ongoing_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('start_date', '<=', $today)->where('end_date', '>=', $today);
        });
        $completed_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('end_date', '<', $today);
        });
        $scheduled_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('start_date', '>', $today);
        });
        $pending_trainings_count = $scheduled_trainings_count;
        $pending_learning_request  = LearningRequest::with('learning')
            ->where('status','Pending')->where('resort_id',$resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('employees', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->get();

        // Compulsory Learning completion % — for probationary employees in scope, what fraction
        // have completed every probationary program assigned to the resort?
        $compulsoryPercent = $this->computeCompulsoryCompletionPercent($resort_id, $scopedEmpIds);

        // "My Compulsory Programs" — if the logged-in user is on probation, list the programs
        // they personally need to complete + their per-program status.
        $myCompulsoryPrograms = $this->buildMyCompulsoryPrograms($resort_id);

        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances'])
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('trainingAttendances', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        // Dynamic "Overdue" employees — most recent Absent attendance records (last 90 days),
        // scoped to the user's department.
        $overdueEmployees = TrainingAttendance::with(['employee.resortAdmin', 'schedule.learningProgram'])
            ->whereHas('schedule', fn($sq) => $sq->where('resort_id', $resort_id))
            ->where('status', 'Absent')
            ->where('attendance_date', '>=', Carbon::now()->subDays(90))
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereIn('employee_id', $scopedEmpIds))
            ->orderByDesc('attendance_date')
            ->limit(5)
            ->get();

        // Dynamic "Top performers" — employees with the most Present records in the last 90 days.
        $topPerformers = TrainingAttendance::selectRaw('employee_id, COUNT(*) as present_count, MAX(attendance_date) as last_date')
            ->whereHas('schedule', fn($sq) => $sq->where('resort_id', $resort_id))
            ->where('status', 'Present')
            ->where('attendance_date', '>=', Carbon::now()->subDays(90))
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereIn('employee_id', $scopedEmpIds))
            ->groupBy('employee_id')
            ->orderByDesc('present_count')
            ->limit(5)
            ->with(['employee.resortAdmin'])
            ->get();

        return view('resorts.learning.dashboard.hoddashboard',compact('page_header','page_title','ongoing_trainings_count','completed_trainings_count','scheduled_trainings_count','pending_trainings_count','pending_learning_request','trainings','overdueEmployees','topPerformers','compulsoryPercent','myCompulsoryPrograms'));
    }

    public function excom_dashboard()
    {
        request()->merge(['dashboard_label' => 'XCOM']);
        return $this->hod_dashboard();
    }

    /**
     * Average evaluation score (0-100) across all submitted training evaluation
     * forms for this resort. Walks each evaluation_form's structure for starRating
     * fields, reads the values from the response payloads, and averages.
     * Returns null if no responses exist (UI can show "—" instead of 0%).
     */
    private function computeAverageEvaluationScore($resortId)
    {
        $tableExists = \Schema::hasTable('training_evaluation_form_responses') || \Schema::hasTable('training_evaluation_responses');
        if (!$tableExists) return null;
        $responsesTable = \Schema::hasTable('training_evaluation_form_responses') ? 'training_evaluation_form_responses' : 'training_evaluation_responses';
        if (!\Schema::hasColumn($responsesTable, 'response_data')) return null;

        $responses = \DB::table($responsesTable)
            ->where('resort_id', $resortId)
            ->whereNotNull('response_data')
            ->pluck('response_data');
        if ($responses->isEmpty()) return null;

        $ratings = [];
        foreach ($responses as $raw) {
            $data = json_decode($raw, true);
            if (!is_array($data)) continue;
            foreach ($data as $val) {
                // Accept any numeric value 1..5 — close enough as a starRating signal.
                if (is_numeric($val) && $val >= 1 && $val <= 5) $ratings[] = (float) $val;
            }
        }
        if (empty($ratings)) return null;
        $avg5 = array_sum($ratings) / count($ratings);
        return (int) round(($avg5 / 5) * 100);
    }

    /**
     * Total hours per learning program for completed/ongoing training schedules.
     * Returns up to 9 entries — used to feed the Learning Hours legend dynamically
     * (replacing the nine hardcoded "Learning 1" labels).
     */
    private function computeLearningHoursByProgram($resortId)
    {
        return \DB::table('training_schedules as ts')
            ->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->where('ts.resort_id', $resortId)
            ->selectRaw('lp.name as name, SUM(COALESCE(lp.hours, 0)) as total_hours, COUNT(ts.id) as session_count')
            ->groupBy('lp.id', 'lp.name')
            ->orderByDesc('session_count')
            ->limit(9)
            ->get();
    }

    /**
     * Aggregate training-attendance breakdown for the Learning Attendance doughnut.
     * Returns counts of Present / Absent / Late so the chart legend is real.
     */
    private function computeLearningAttendanceBreakdown($resortId)
    {
        return \DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resortId)
            ->selectRaw('ta.status as status, COUNT(*) as n')
            ->groupBy('ta.status')
            ->pluck('n', 'status')
            ->toArray();
    }

    /**
     * If the logged-in user is on probation (or marked Probationary), return
     * the resort's probationary programs along with their personal completion
     * state so the dashboard can list "My Compulsory Programs". Empty array
     * for non-probationary users — the blade hides the panel in that case.
     */
    private function buildMyCompulsoryPrograms($resortId)
    {
        $emp = $this->resort->GetEmployee ?? null;
        if (!$emp) return [];

        $isOnProbation = ($emp->employment_type === 'Probationary')
            || in_array($emp->probation_status, ['Active', 'Extended']);
        if (!$isOnProbation) return [];

        $required = \App\Models\ProbationaryLearningProgram::with('program')
            ->where('resort_id', $resortId)
            ->get();
        if ($required->isEmpty()) return [];

        // Build a quick lookup of which required programs this employee has 'Present' attendance for.
        $programIds = $required->pluck('program_id')->all();
        $completedProgramIds = \DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resortId)
            ->whereIn('ts.training_id', $programIds)
            ->where('ta.employee_id', $emp->id)
            ->where('ta.status', 'Present')
            ->pluck('ts.training_id')
            ->unique()
            ->all();

        $deadline = $emp->joining_date ? \Carbon\Carbon::parse($emp->joining_date) : null;

        $list = $required->map(function ($r) use ($completedProgramIds, $deadline) {
            $isCompleted = in_array($r->program_id, $completedProgramIds);
            $dueOn = ($deadline && $r->completion_days)
                ? (clone $deadline)->addDays((int) $r->completion_days)
                : null;
            return (object) [
                'program_id'      => $r->program_id,
                'program_name'    => optional($r->program)->name ?? '—',
                'completion_days' => $r->completion_days,
                'due_on'          => $dueOn,
                'is_completed'    => $isCompleted,
                'is_overdue'      => !$isCompleted && $dueOn && $dueOn->isPast(),
            ];
        })->all();

        // Fire once-only reminder notifications for each pending/overdue program.
        $this->sendProbationaryReminders($emp, $list);

        return $list;
    }

    /**
     * For each pending or overdue probationary program, send the user (and the manager,
     * for overdue) a single notification — guarded by probationary_program_notifications
     * so re-opening the dashboard doesn't re-spam.
     */
    private function sendProbationaryReminders($emp, $list)
    {
        if (!$emp || empty($list)) return;
        $resortId = $emp->resort_id;

        foreach ($list as $p) {
            if ($p->is_completed) continue;

            $kind = $p->is_overdue ? 'overdue' : 'pending';

            // Idempotent: try to insert the tracker row; if a duplicate, skip the send.
            $inserted = false;
            try {
                \DB::table('probationary_program_notifications')->insert([
                    'employee_id' => $emp->id,
                    'program_id'  => $p->program_id,
                    'kind'        => $kind,
                    'sent_at'     => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $inserted = true;
            } catch (\Illuminate\Database\QueryException $e) {
                // Unique constraint hit — already notified; skip.
            }
            if (!$inserted) continue;

            try {
                $title = $kind === 'overdue' ? 'Compulsory Training Overdue' : 'Compulsory Training Reminder';
                $msg = $kind === 'overdue'
                    ? 'Your compulsory probation training "' . $p->program_name . '" is overdue. Please complete it as soon as possible.'
                    : 'Reminder: please complete your probation training "' . $p->program_name . '"'
                        . ($p->due_on ? ' by ' . \Carbon\Carbon::parse($p->due_on)->format('d M Y') : '') . '.';

                $recipients = [(int) $emp->id];
                if ($kind === 'overdue' && $emp->reporting_to) {
                    $recipients[] = (int) $emp->reporting_to;
                }

                Common::notifyEmployees($resortId, $recipients, $title, $msg, 'Learning', $p->program_id, 'learning-probationary-reminder');
            } catch (\Exception $e) {
                \Log::warning('Probationary reminder send failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Compulsory Learning completion % — for probationary employees in scope, what
     * fraction of (employee × required-program) pairs have a completed training-
     * attendance record. Returns an int 0..100 (or null if there's nothing to measure
     * so the UI can show "—" instead of a misleading 0%).
     */
    private function computeCompulsoryCompletionPercent($resortId, $scopedEmpIds)
    {
        $programIds = \App\Models\ProbationaryLearningProgram::where('resort_id', $resortId)->pluck('program_id');
        if ($programIds->isEmpty()) return null;

        // "Probationary" = either employment_type='Probationary' OR probation_status='Active'/'Extended'.
        // Test data on this app frequently has employment_type='Full-Time' while still in
        // probation period, so we can't rely on the type alone.
        $probationary = \App\Models\Employee::where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where(function ($q) {
                $q->where('employment_type', 'Probationary')
                  ->orWhereIn('probation_status', ['Active', 'Extended']);
            })
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereIn('id', $scopedEmpIds))
            ->pluck('id');
        if ($probationary->isEmpty()) return null;

        $totalPairs = $probationary->count() * $programIds->count();

        // Count completions: an attendance record with status='Present' on a TrainingSchedule
        // backed by one of the required programs, for one of the in-scope probationary employees.
        $completedPairs = \DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resortId)
            ->whereIn('ts.training_id', $programIds)
            ->whereIn('ta.employee_id', $probationary)
            ->where('ta.status', 'Present')
            ->distinct()
            ->count(\DB::raw('CONCAT(ta.employee_id, "-", ts.training_id)'));

        return $totalPairs > 0 ? (int) round(($completedPairs / $totalPairs) * 100) : null;
    }

    public function manager_dashboard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;
        $today = \Carbon\Carbon::now()->toDateString();

        // Department-visibility scope. GM / HR / L&D Manager (and admins) get null
        // (full resort access); HOD / EXCOM / regular managers get only their own
        // department's employee IDs. All counts and lists below honour this.
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();

        // Helper closures so we don't duplicate the scope-when clause everywhere.
        $applyScope = function ($q) use ($scopedEmpIds) {
            if (is_array($scopedEmpIds)) {
                $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds));
            }
        };

        // Tile counts derive directly from dates so they reflect reality even when
        // nobody manually advanced TrainingSchedule.status. Plus the participants
        // scope so HOD/MGR only see their own dept's training counts.
        $countByDateRule = function ($builderTweak) use ($resort_id, $applyScope) {
            $q = TrainingSchedule::where('resort_id', $resort_id);
            $builderTweak($q);
            $applyScope($q);
            return $q->count();
        };
        // Pure date-derived counts so they exactly match the schedule list filters
        // (raw `status` column is unreliable — every row defaults to 'Scheduled').
        $ongoing_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('start_date', '<=', $today)->where('end_date', '>=', $today);
        });
        $completed_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('end_date', '<', $today);
        });
        $scheduled_trainings_count = $countByDateRule(function ($q) use ($today) {
            $q->where('start_date', '>', $today);
        });
        $pending_trainings_count = $scheduled_trainings_count;

        // Completed compulsory learning = mandatory-program schedules whose end_date
        // has passed (or status explicitly Completed) — scoped to in-scope participants.
        $compulsory_completed_traing = $countByDateRule(function ($q) use ($today, $resort_id) {
            $q->where(function ($qq) use ($today) {
                $qq->where('status', 'Completed')->orWhere('end_date', '<', $today);
            })
            ->whereIn('training_id', function ($q) use ($resort_id) {
                $q->select('program_id')
                    ->from('mandatory_learning_programs')
                    ->where('resort_id', $resort_id);
            });
        });

        // Pending learning requests for in-scope employees only.
        $pending_learning_request = LearningRequest::with('learning')
            ->where('status', 'Pending')
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('employees', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->get();

        $categories = LearningCategory::withCount('programs')->where('resort_id', $resort_id)->get();
        // Recent absentees (last 90 days) — "today only" produced an empty list when
        // no one was marked absent today. Mirrors the HOD dashboard's overdue list.
        $absentees = TrainingAttendance::where('status', 'Absent')
            ->where('attendance_date', '>=', \Carbon\Carbon::now()->subDays(90))
            ->whereHas('schedule', fn($q) => $q->where('resort_id', $resort_id))
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereIn('employee_id', $scopedEmpIds))
            ->with('employee.resortAdmin', 'schedule.learningProgram')
            ->orderByDesc('attendance_date')
            ->limit(10)
            ->get();
        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances'])
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        [$avgFeedbackScore, $topTrainerName] = $this->computeFeedbackStats($resort_id);

        // Show "My Compulsory Programs" panel only when the auth user is on probation
        // (re-uses the HOD/XCOM dashboard's logic so behaviour is identical).
        $myCompulsoryPrograms = $this->buildMyCompulsoryPrograms($resort_id);

        // Team-wide list of employees with pending or overdue compulsory programs,
        // so the L&D Manager can spot who needs scheduling and click through to
        // /resort/learning/schedule with the employee + program pre-selected.
        // Dashboard shows the most-urgent 5 — full list lives on a separate page
        // accessed via the "View All" link.
        $teamCompulsoryPendingAll = $this->buildTeamCompulsoryPending($resort_id);
        $teamCompulsoryPendingCount = count($teamCompulsoryPendingAll);
        $teamCompulsoryPending = array_slice($teamCompulsoryPendingAll, 0, 10);

        return view('resorts.learning.dashboard.manager-dashboard', compact(
            'page_title','scheduled_trainings_count','pending_trainings_count','completed_trainings_count',
            'ongoing_trainings_count','compulsory_completed_traing','pending_learning_request',
            'trainings','categories','absentees','avgFeedbackScore','topTrainerName',
            'myCompulsoryPrograms','teamCompulsoryPending','teamCompulsoryPendingCount'
        ));
    }

    /**
     * Full-page list (with pagination) of every probationer's pending / overdue
     * compulsory programs. Linked from the dashboard's "View All".
     */
    public function teamCompulsoryPendingAll(Request $request)
    {
        $page_title = 'Compulsory Trainings — Action Needed';
        $resort_id = $this->resort->resort_id;
        $rows = $this->buildTeamCompulsoryPending($resort_id);

        $perPage = (int) ($request->input('per_page') ?: 15);
        $perPage = max(5, min($perPage, 100));
        $current = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $items = array_slice($rows, ($current - 1) * $perPage, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            count($rows),
            $perPage,
            $current,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('resorts.learning.dashboard.compulsory-pending', compact('page_title', 'paginator'));
    }

    /**
     * Returns one row per (probationer × required-compulsory-program) where the
     * employee hasn't yet been marked Present in any session of that program.
     * Each row includes an `is_overdue` flag for the UI badge.
     */
    private function buildTeamCompulsoryPending($resortId)
    {
        $required = \App\Models\ProbationaryLearningProgram::with('program')
            ->where('resort_id', $resortId)
            ->get();
        if ($required->isEmpty()) return [];

        // HOD / MGR / SUP only see probationers within their dept; GM / HR /
        // L&D Manager / admin see every probationer (helper returns null).
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();

        // "On probation NOW" means the employee is in their probation window —
        // either probation_end_date is still in the future, OR they joined
        // within the last 3 months (the default 3-month window when no
        // explicit end date is set). This stops long-tenured employees with
        // stale `probation_status='Active'` from showing up as probationers
        // (which was inflating the list to 44 rows on prod when only a few
        // are actually in their first 3 months).
        $today    = \Carbon\Carbon::today();
        $cutoff3m = $today->copy()->subMonths(3)->toDateString();
        $todayStr = $today->toDateString();

        $probationers = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where(function ($q) {
                $q->where('employment_type', 'Probationary')
                  ->orWhereIn('probation_status', ['Active', 'Extended']);
            })
            ->where(function ($q) use ($todayStr, $cutoff3m) {
                $q->whereDate('probation_end_date', '>=', $todayStr)
                  ->orWhere(function ($qq) use ($cutoff3m) {
                      $qq->whereNull('probation_end_date')
                         ->whereDate('joining_date', '>=', $cutoff3m);
                  });
            })
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereIn('id', $scopedEmpIds))
            ->get();
        if ($probationers->isEmpty()) return [];

        // Pre-compute who has already completed which program (Present in any session).
        $programIds = $required->pluck('program_id')->all();
        $completed = \DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resortId)
            ->whereIn('ts.training_id', $programIds)
            ->where('ta.status', 'Present')
            ->select('ta.employee_id', 'ts.training_id')
            ->get()
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->pluck('training_id')->unique()->all())
            ->toArray();

        // Pre-compute (employee × program) pairs that already have an in-flight
        // schedule — a session whose end_date hasn't passed yet. The page is
        // "Action Needed" for L&D, so once a session is booked the row leaves
        // the list. If the session ends without Present attendance, the row
        // reappears as Overdue on the next render.
        $today = \Carbon\Carbon::now()->toDateString();
        $scheduled = \DB::table('training_participants as tp')
            ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
            ->where('ts.resort_id', $resortId)
            ->whereIn('ts.training_id', $programIds)
            ->where('ts.end_date', '>=', $today)
            ->select('tp.employee_id', 'ts.training_id')
            ->get()
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->pluck('training_id')->unique()->all())
            ->toArray();

        $rows = [];
        foreach ($probationers as $emp) {
            $deadlineAnchor = $emp->joining_date ? \Carbon\Carbon::parse($emp->joining_date) : null;
            $empCompleted = $completed[$emp->id] ?? [];
            $empScheduled = $scheduled[$emp->id] ?? [];

            foreach ($required as $r) {
                if (in_array($r->program_id, $empCompleted, true)) continue; // already completed
                if (in_array($r->program_id, $empScheduled, true)) continue; // session already booked, not yet ended

                $dueOn = ($deadlineAnchor && $r->completion_days)
                    ? (clone $deadlineAnchor)->addDays((int) $r->completion_days)
                    : null;
                $rows[] = (object) [
                    'employee_id'   => $emp->id,
                    'employee_name' => optional($emp->resortAdmin)->full_name
                        ?? trim((optional($emp->resortAdmin)->first_name ?? '') . ' ' . (optional($emp->resortAdmin)->last_name ?? '')),
                    'department'    => optional($emp->department)->name,
                    'position'      => optional($emp->position)->position_title,
                    'program_id'    => $r->program_id,
                    'program_name'  => optional($r->program)->name ?? '—',
                    'due_on'        => $dueOn,
                    'is_overdue'    => $dueOn && $dueOn->isPast(),
                ];
            }
        }

        // Sort overdue first, then by due date ascending.
        usort($rows, function ($a, $b) {
            if ($a->is_overdue !== $b->is_overdue) return $a->is_overdue ? -1 : 1;
            $aT = $a->due_on ? $a->due_on->timestamp : PHP_INT_MAX;
            $bT = $b->due_on ? $b->due_on->timestamp : PHP_INT_MAX;
            return $aT <=> $bT;
        });

        return $rows;
    }

    /**
     * Aggregate feedback responses for a resort into a single percent score and
     * the name of the highest-rated trainer. Responses are stored as form-builder
     * JSON, so values are option-N strings — we map N to a numeric score and
     * normalise against the highest option seen.
     */
    private function computeFeedbackStats($resort_id)
    {
        $responses = \DB::table('training_feedback_responses as tfr')
            ->join('training_schedules as ts', 'ts.id', '=', 'tfr.training_id')
            ->where('ts.resort_id', $resort_id)
            ->select('tfr.responses', 'ts.training_id as schedule_id')
            ->get();

        if ($responses->isEmpty()) {
            return [0, null];
        }

        $trainerByScheduleId = \DB::table('training_schedules as ts')
            ->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->where('ts.resort_id', $resort_id)
            ->pluck('lp.trainer', 'ts.id');

        $sumScore = 0;
        $countScore = 0;
        $maxOption = 1;
        $perTrainer = [];

        foreach ($responses as $r) {
            $data = json_decode($r->responses, true);
            if (!is_array($data)) continue;
            $trainerId = $trainerByScheduleId[$r->schedule_id] ?? null;
            foreach ($data as $val) {
                if (is_string($val) && preg_match('/^option-(\d+)$/', $val, $m)) {
                    $score = (int) $m[1];
                    $sumScore += $score;
                    $countScore++;
                    if ($score > $maxOption) $maxOption = $score;
                    if ($trainerId) {
                        $perTrainer[$trainerId]['sum'] = ($perTrainer[$trainerId]['sum'] ?? 0) + $score;
                        $perTrainer[$trainerId]['count'] = ($perTrainer[$trainerId]['count'] ?? 0) + 1;
                    }
                }
            }
        }

        $avgPercent = $countScore > 0 ? (int) round(($sumScore / $countScore) / max($maxOption, 1) * 100) : 0;

        $topTrainerId = null;
        $topAvg = -1;
        foreach ($perTrainer as $tid => $s) {
            $avg = $s['sum'] / $s['count'];
            if ($avg > $topAvg) {
                $topAvg = $avg;
                $topTrainerId = $tid;
            }
        }

        $topTrainerName = null;
        if ($topTrainerId) {
            $emp = Employee::with('resortAdmin')->find($topTrainerId);
            if ($emp && $emp->resortAdmin) {
                $topTrainerName = trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name);
            }
        }

        return [$avgPercent, $topTrainerName];
    }

    /**
     * AJAX: stacked bar data for the Onboarding Learning chart on the manager dashboard.
     * X axis = onboarding learning programs in this resort. Stacks = participant counts
     * per department across each program's schedules.
     *
     * Broadened from "onboarding category only" to "every learning program with
     * at least one scheduled session" — see Option B in the dashboard discussion.
     * Endpoint name kept for backwards compatibility with the blade's AJAX call.
     */
    public function onboardingChartData()
    {
        $resort_id = $this->resort->resort_id;
        $palette = ['#014653', '#2EACB3', '#FED049', '#8DC9C9', '#333333', '#3a88fe', '#a264f7', '#ff4013'];

        // Only show programs that actually have scheduled sessions in this resort —
        // otherwise the x-axis fills with empty bars for every program ever defined.
        $programs = \DB::table('learning_programs as lp')
            ->join('training_schedules as ts', 'ts.training_id', '=', 'lp.id')
            ->where('lp.resort_id', $resort_id)
            ->where('ts.resort_id', $resort_id)
            ->select('lp.id', 'lp.name')
            ->distinct()
            ->orderBy('lp.name')
            ->get();

        if ($programs->isEmpty()) {
            return response()->json(['success' => true, 'data' => ['labels' => [], 'datasets' => []]]);
        }

        $programIds = $programs->pluck('id');

        $rows = \DB::table('training_participants as tp')
            ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
            ->join('employees as e', 'e.id', '=', 'tp.employee_id')
            ->join('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('ts.resort_id', $resort_id)
            ->whereIn('ts.training_id', $programIds)
            ->select('ts.training_id as program_id', 'd.id as dept_id', 'd.name as dept_name', \DB::raw('COUNT(DISTINCT tp.employee_id) as participants'))
            ->groupBy('ts.training_id', 'd.id', 'd.name')
            ->get();

        $labels = $programs->pluck('name')->values()->all();
        $programIdToIndex = [];
        foreach ($programs as $i => $p) {
            $programIdToIndex[$p->id] = $i;
        }

        $byDept = [];
        foreach ($rows as $row) {
            if (!isset($byDept[$row->dept_id])) {
                $byDept[$row->dept_id] = [
                    'name' => $row->dept_name,
                    'data' => array_fill(0, count($labels), 0),
                ];
            }
            $byDept[$row->dept_id]['data'][$programIdToIndex[$row->program_id]] = (int) $row->participants;
        }

        $datasets = [];
        $i = 0;
        foreach ($byDept as $dept) {
            $color = $palette[$i % count($palette)];
            $datasets[] = [
                'label'           => $dept['name'],
                'data'            => $dept['data'],
                'backgroundColor' => $color,
                'borderColor'     => '#fff',
                'borderWidth'     => 2,
                'borderRadius'    => 10,
            ];
            $i++;
        }

        return response()->json(['success' => true, 'data' => ['labels' => $labels, 'datasets' => $datasets]]);
    }

    public function details(Request $request, $id)
    {
        if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Details';
        $request_detail = LearningRequest::with('learning','employees.employee.resortAdmin')->where('id',$id)->first();
        $createdBy = ResortAdmin::where('id',$request_detail->created_by)->first();
        // dd($request_detail);
        return view('resorts.learning.request.detail',compact('page_title','request_detail','createdBy'));
    }

    public function getAbsentees(){
        $page_title = "Absentees List";
        return view('resorts.learning.attendance.absentees',compact('page_title'));
    }

    public function getAllAbsenteesData(Request $request) {
        $resort_id = $this->resort->resort_id;
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();

        $query = TrainingAttendance::where('status', 'Absent')
            ->with(['employee.resortAdmin', 'schedule.learningProgram'])
            // Bind to this resort + the user's department visibility scope.
            ->whereHas('schedule', fn($sq) => $sq->where('resort_id', $resort_id))
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereIn('employee_id', $scopedEmpIds))
            ->orderBy('attendance_date', 'desc');

        // Apply search filter
        if ($request->has('searchTerm') && !empty($request->searchTerm)) {
            $query->whereHas('employee.resortAdmin', function ($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->searchTerm . '%')
                ->orwhere('last_name', 'LIKE', '%' . $request->searchTerm . '%');
            });
        }
    
        // Apply date filter
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('attendance_date', $request->date);
        }
    
        $absentees = $query->get()->map(function ($absentee) {
            return [
                'employee_name' => $absentee->employee->resortAdmin->full_name,
                'learning_name' => $absentee->schedule->learningProgram->name,
                'attendance_date' => $absentee->attendance_date,
                'profile_picture' => Common::getResortUserPicture($absentee->employee->resortAdmin->id) // Get image URL
            ];
        });
        // dd($absentees);
    
        return response()->json(['data' => $absentees]);
    }
    
}