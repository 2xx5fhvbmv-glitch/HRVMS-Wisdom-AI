<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV slice 5a: Workforce Planning. Runs after DemoOrganisationSeeder.
 *
 * Rows are written in the shapes ManningResponseController, ResortAllNotificationController and
 * BudgetController write them:
 * - manning_responses: one row per department × category (Permanent / Casual / Intern) × year, each with 12
 *   position_monthly_data rows per position (headcount = budgeted seats, filledcount = staff in them now,
 *   vacantcount = the difference). The dashboard tiles and Consolidated Budget read the stored counts; the
 *   Manning and View Budget pages recount filled seats from Active staff — both agree because they match.
 * - This year's budget went through last year's request and is approved by the GM. Next year's request is
 *   out to the HODs now: every department but one has answered (budget_statuses 'Genrated' / 'Respond to
 *   HR', child notification 'Yes'); the last one is still pending ('No', no manning response yet).
 * - resort_vacant_budget_costs: one row per vacant permanent seat with its salary — without it the budget
 *   prices a vacant seat at $0.
 * - occuplanies: seasonal daily occupancy from 13 months back to 3 months ahead (the dashboard shows today
 *   ± 5 days; the AI staffing forecast averages each month).
 * Cost configuration lines come from data/config.php.
 */
class DemoWorkforceSeeder
{
    const ROOMS = 120;
    // Average occupancy % by month — Maldives high season is December to April.
    const SEASON = [1 => 88, 2 => 91, 3 => 86, 4 => 80, 5 => 66, 6 => 57, 7 => 63, 8 => 68, 9 => 60, 10 => 71, 11 => 79, 12 => 90];
    // Monthly salary (USD) budgeted for a seat nobody holds yet, by rank.
    const SEAT_SALARY = [1 => 7000, 2 => 3600, 4 => 2200, 5 => 1150, 6 => 700, 0 => 450];
    const PENDING_DEPT = 'Engineering'; // hasn't answered next year's request yet

    private float $rate;
    private array $counts = ['manning responses' => 0, 'vacant seats' => 0, 'occupancy days' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $year = (int) date('Y');
        $this->rate = (float) DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('DollertoMVR') ?: 15.42;
        $depts = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('status', 'active')->orderBy('id')->get(['id', 'name']);
        $positions = DB::table('resort_positions')->where('resort_id', $this->resortId)->orderBy('id')
            ->get(['id', 'dept_id', 'employee_category', 'Rank'])->groupBy('dept_id');
        $staff = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')
            ->get(['Dept_id', 'Position_id', 'rank', 'basic_salary', 'basic_salary_currency']);
        $hodPosition = $staff->where('rank', 2)->keyBy('Dept_id')->map(fn ($e) => $e->Position_id);

        // Open seats this year: every budgeted-but-empty position, plus one seat on some line positions.
        // Next year a few busy line positions grow by one more.
        $seats = [];
        foreach ($positions->flatten() as $p) {
            $filled = $staff->where('Position_id', $p->id)->count();
            $open = $filled === 0 ? mt_rand(1, 2) : (in_array((int) $p->Rank, [0, 6], true) && $filled >= 2 && $this->chance(0.35) ? 1 : 0);
            $growth = $filled >= 4 && $this->chance(0.4) ? 1 : 0;
            $seats[$p->id] = [$filled, $open, $growth];
        }

        // This year: requested in September last year, approved by the GM in November.
        $this->request($year, $depts, $positions, $staff, $seats, $hodPosition, Carbon::create($year - 1, 9, 10, 10), true);
        // Next year: sent three weeks ago, all departments but one have answered.
        $this->request($year + 1, $depts, $positions, $staff, $seats, $hodPosition, Carbon::today()->subWeeks(3)->setTime(10, 0), false);
        $this->occupancy();
        return $this->counts;
    }

    private function request(int $year, $depts, $positions, $staff, array $seats, $hodPosition, Carbon $sent, bool $approved): void
    {
        $messageId = 'DR' . mt_rand(100000000, 2147483647);
        $stamp = ['created_by' => $this->adminId, 'modified_by' => $this->adminId];
        DB::table('resorts_parent_notifications')->insert($stamp + ['resort_id' => $this->resortId, 'user_type' => 'sub', 'user_id' => $this->adminId,
            'message_id' => $messageId, 'status' => 'Active',
            'message_subject' => "This is a Manning Request for additional manpower requirements for {$year}. Please review and update your response accordingly.",
            'created_at' => $sent, 'updated_at' => $sent]);

        foreach ($depts as $i => $dept) {
            $answered = $approved || $dept->name !== self::PENDING_DEPT;
            $answeredAt = $sent->copy()->addDays(2 + $i)->setTime(11 + $i % 5, 15);
            DB::table('resorts_child_notifications')->insert($stamp + ['Parent_msg_id' => $messageId, 'Department_id' => $dept->id,
                'Position_id' => (int) ($hodPosition[$dept->id] ?? 0), 'response' => $answered ? 'Yes' : 'No',
                'created_at' => $sent, 'updated_at' => $answered ? $answeredAt : $sent]);
            if (!$answered) {
                continue;
            }
            foreach (['Permanent' => null, 'Casual' => 'Casual', 'Intern' => 'Intern'] as $type => $category) {
                $deptPositions = ($positions[$dept->id] ?? collect())->where('employee_category', $category);
                if ($deptPositions->isEmpty()) {
                    continue;
                }
                $monthly = [];
                $totals = [0, 0, 0];
                foreach ($deptPositions as $p) {
                    [$filled, $open, $growth] = $seats[$p->id];
                    $headcount = $filled + $open + ($approved ? 0 : $growth);
                    $totals = [$totals[0] + $headcount, $totals[1] + $filled, $totals[2] + $headcount - $filled];
                    for ($m = 1; $m <= 12; $m++) {
                        $monthly[] = ['position_id' => $p->id, 'month' => $m, 'headcount' => $headcount, 'filledcount' => $filled,
                            'vacantcount' => $headcount - $filled];
                    }
                    if (!$category) {
                        $this->vacantSeats($year, $dept->id, $p, $staff, $headcount - $filled, !$approved);
                    }
                }
                $responseId = DB::table('manning_responses')->insertGetId(['resort_id' => $this->resortId, 'dept_id' => $dept->id, 'year' => $year,
                    'employment_type' => $type, 'status' => 'submitted', 'total_headcount' => $totals[0], 'total_filled_positions' => $totals[1],
                    'total_vacant_positions' => $totals[2], 'budget_process_status' => $approved ? 'Approved' : null,
                    'created_at' => $answeredAt, 'updated_at' => $approved ? $sent->copy()->addMonths(2) : $answeredAt]);
                DB::table('position_monthly_data')->insert(array_map(fn ($r) => $r + ['manning_response_id' => $responseId,
                    'created_at' => $answeredAt, 'updated_at' => $answeredAt], $monthly));
                DB::table('budget_statuses')->insert($stamp + ['resort_id' => $this->resortId, 'Department_id' => $dept->id, 'message_id' => $messageId,
                    'Budget_id' => $responseId, 'status' => $approved ? 'Approved' : 'Genrated', 'comments' => $approved ? 'Approved' : 'Respond to HR',
                    'OtherComments' => '', 'created_at' => $answeredAt, 'updated_at' => $approved ? $sent->copy()->addMonths(2) : $answeredAt]);
                $this->counts['manning responses']++;
            }
        }
    }

    /** One budget row per vacant permanent seat: the position's going rate (next year with a 3% raise). */
    private function vacantSeats(int $year, int $deptId, object $position, $staff, int $vacant, bool $nextYear): void
    {
        if ($vacant <= 0) {
            return;
        }
        $holders = $staff->where('Position_id', $position->id);
        $salary = $holders->isNotEmpty()
            ? $holders->avg(fn ($e) => (float) $e->basic_salary / ($e->basic_salary_currency === 'MVR' ? $this->rate : 1))
            : self::SEAT_SALARY[(int) $position->Rank] ?? self::SEAT_SALARY[6];
        $salary = round($salary, -1);
        for ($i = 1; $i <= $vacant; $i++) {
            DB::table('resort_vacant_budget_costs')->insert(['position_id' => $position->id, 'department_id' => $deptId, 'resort_id' => $this->resortId,
                'year' => $year, 'vacant_index' => $i, 'details' => 'Both', 'basic_salary' => $salary,
                'current_salary' => $nextYear ? round($salary * 1.03, 2) : $salary, 'created_by' => $this->adminId, 'modified_by' => $this->adminId,
                'created_at' => now(), 'updated_at' => now()]);
            $this->counts['vacant seats'] += $nextYear ? 0 : 1;
        }
    }

    /** Daily occupancy as the Occupancy screen saves it (Y-m-d date, 0–100 percent). */
    private function occupancy(): void
    {
        $rows = [];
        $end = Carbon::today()->addMonths(3);
        for ($d = Carbon::today()->startOfMonth()->subMonths(13); $d->lte($end); $d->addDay()) {
            $percent = max(35, min(99, self::SEASON[$d->month] + mt_rand(-60, 60) / 10 + ($d->isWeekend() ? 2 : 0)));
            $rows[] = ['resort_id' => $this->resortId, 'occupancydate' => $d->format('Y-m-d'), 'occupancyinPer' => round($percent, 1),
                'occupancytotalRooms' => self::ROOMS, 'occupancyOccupiedRooms' => (int) round(self::ROOMS * $percent / 100),
                'created_by' => $this->adminId, 'modified_by' => $this->adminId, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('occuplanies')->insert($chunk);
        }
        $this->counts['occupancy days'] = count($rows);
    }

    private function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }
}
