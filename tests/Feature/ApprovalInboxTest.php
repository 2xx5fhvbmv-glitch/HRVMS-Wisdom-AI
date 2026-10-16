<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/**
 * People → Approvals: every type is listed only to the approver its own module
 * lets act, and the row's buttons post exactly what that module's endpoint needs
 * (so acting here = acting in the module), after which the row is gone.
 * Built on the Demo ENV organisation (HR Director, Finance Director, GM, HODs).
 */
class ApprovalInboxTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
    }

    /** The inbox rows the page's DataTable would get for this user. */
    private function inbox(string $login, array $query = []): array
    {
        return $this->as($login)->getJson(route('people.approvel.index', $query + ['length' => 500]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
    }

    private function row(string $login, string $type, string $nameContains = ''): ?array
    {
        foreach ($this->inbox($login) as $r) {
            if ($r['request_type'] === $type && ($nameContains === '' || str_contains((string) $r['name'], $nameContains))) {
                return $r;
            }
        }
        return null;
    }

    /** Click a row button the way list.blade.php does: {status, action, key: id} + reason keys + data-payload. */
    private function click(string $login, array $row, string $button, string $reason = 'Checked in test')
    {
        $flag = ['approve' => null, 'hold' => 'On-Hold', 'reject' => 'Closed'][$button];
        preg_match_all('/<a\b([^>]*)>/', $row['action'], $tags);
        $attrs = null;
        foreach ($tags[1] as $tag) {
            preg_match_all('/([\w-]+)="([^"]*)"/', $tag, $m, PREG_SET_ORDER);
            $a = [];
            foreach ($m as [, $k, $v]) {
                $a[$k] = html_entity_decode($v, ENT_QUOTES);
            }
            if (str_contains($a['class'] ?? '', 'action-btn') && ($a['data-flag'] ?? null) === $flag) {
                $attrs = $a;
                break;
            }
        }
        $this->assertNotNull($attrs, "{$row['request_type']} row has a {$button} button");

        $url = $attrs['data-approve_url'] ?? $attrs['data-hold_url'] ?? $attrs['data-reject_url'];
        $isReasoned = $button === 'reject' || !empty($attrs['data-needs_reason']);
        $data = ['status' => $button === 'reject' ? 'Rejected' : $attrs['data-status'], 'action' => $attrs['data-action']];
        if ($attrs['data-key'] !== '') {
            $data[$attrs['data-key']] = $attrs['data-req_id'];
        }
        if ($isReasoned) {
            foreach (['reject_reason', 'rejection_reason', 'hold_reason', 'reason', 'remarks', 'ReviseBudgetComment'] as $k) {
                $data[$k] = $reason;
            }
        }
        $data = array_merge($data, json_decode($attrs['data-payload'], true) ?: []);

        $this->as($login)->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $response = $attrs['data-method'] === 'GET'
            ? $this->getJson($url . (str_contains($url, '?') ? '&' : '?') . http_build_query($data), ['X-Requested-With' => 'XMLHttpRequest'])
            : $this->postJson($url, $data, ['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertLessThan(400, $response->status(), "{$row['request_type']} {$button}: " . $response->getContent());
        $this->assertNotFalse($response->json('success') ?? true, "{$row['request_type']} {$button}: " . $response->getContent());
        return $response;
    }

    public function test_salary_advance_moves_hr_then_finance_and_is_hidden_from_others()
    {
        $staff = $this->emp('employee');
        $id = DB::table('payroll_advance')->insertGetId(['employee_id' => $staff->id, 'resort_id' => $this->resortId, 'request_type' => 'Salary Advance',
            'request_date' => now()->toDateString(), 'pourpose' => 'Family', 'request_amount' => 300, 'hr_status' => 'Pending', 'finance_status' => 'Pending',
            'gm_status' => 'Pending', 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payroll_advance_guarantor')->insert(['payroll_advance_id' => $id, 'guarantor_id' => $this->emp('supervisor')->id, 'status' => 'Approved',
            'created_at' => now(), 'updated_at' => now()]);
        $name = $staff->resortAdmin->full_name;

        $this->assertNull($this->row('hod.housekeeping', 'Payroll Advance', $name), 'not every advance for everyone any more');
        $this->assertNull($this->row('finance', 'Payroll Advance', $name), 'Finance waits for HR');
        $row = $this->row('hr', 'Payroll Advance', $name);
        $this->assertNotNull($row, 'the rank-1 HR Director sees the HR stage');

        $this->click('hr', $row, 'approve');
        $this->assertSame('Approved', DB::table('payroll_advance')->where('id', $id)->value('hr_status'));
        $this->assertNull($this->row('hr', 'Payroll Advance', $name), 'gone once acted on');
        $this->assertNotNull($row = $this->row('finance', 'Payroll Advance', $name), 'now Finance\'s turn');

        $this->click('finance', $row, 'reject', 'Over the limit');
        $this->assertSame('Rejected', DB::table('payroll_advance')->where('id', $id)->value('finance_status'));
        $this->assertNull($this->row('finance', 'Payroll Advance', $name));
    }

    public function test_resignation_hold_then_hod_approve_then_hr_director_sees_it()
    {
        $staff = $this->emp('employee');
        $id = DB::table('employee_resignation')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $staff->id, 'reason' => 'Relocation',
            'resignation_date' => now()->toDateString(), 'hod_id' => $this->emp('hod.housekeeping')->id, 'hr_id' => $this->emp('hr')->id,
            'status' => 'Pending', 'hod_status' => 'Pending', 'hr_status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        $name = $staff->resortAdmin->full_name;

        $this->assertNull($this->row('hr', 'Resignation Request', $name), 'HR waits for the HOD');
        $row = $this->row('hod.housekeeping', 'Resignation Request', $name);
        $this->assertNotNull($row);

        $this->click('hod.housekeeping', $row, 'hold', 'Talking to the employee first');
        $r = DB::table('employee_resignation')->where('id', $id)->first();
        $this->assertSame(['On Hold', 'Talking to the employee first', 'Pending'], [$r->status, $r->hold_reason, $r->hod_status]);
        $this->assertNotNull($row = $this->row('hod.housekeeping', 'Resignation Request', $name), 'still listed while on hold, so it can be resumed');

        $this->click('hod.housekeeping', $row, 'approve');
        $this->assertSame('Approved', DB::table('employee_resignation')->where('id', $id)->value('hod_status'));
        $this->assertNull($this->row('hod.housekeeping', 'Resignation Request', $name));
        $this->assertNotNull($this->row('hr', 'Resignation Request', $name), 'rank-1 HR Director gets the HR stage');
    }

    public function test_payroll_reject_from_the_inbox_rejects_and_approve_hands_to_hr()
    {
        $payroll = fn () => DB::table('payroll')->insertGetId(['resort_id' => $this->resortId, 'start_date' => '2026-09-01', 'end_date' => '2026-09-30',
            'total_payroll' => 1000, 'total_employees' => 100, 'draft_date' => now(), 'payment_date' => now(), 'status' => 'pending_approval',
            'created_at' => now(), 'updated_at' => now()]);
        $steps = function ($pid) {
            foreach ([1 => 'Finance EXCOM', 2 => 'HR EXCOM', 3 => 'GM'] as $order => $title) {
                DB::table('payroll_approvals')->insert(['payroll_id' => $pid, 'resort_id' => $this->resortId, 'step_order' => $order, 'role_title' => $title,
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
        };
        $steps($rejected = $payroll());

        $this->assertNull($this->row('hr', 'Payroll Approval'), 'HR waits for Finance');
        $this->assertNull($this->row('hod.housekeeping', 'Payroll Approval'));
        $row = $this->row('finance', 'Payroll Approval');
        $this->assertNotNull($row);
        $this->click('finance', $row, 'reject', 'Overtime totals wrong');
        $this->assertSame('rejected', DB::table('payroll_approvals')->where('payroll_id', $rejected)->where('step_order', 1)->value('status'), 'reject is a reject, not an approve');
        $this->assertSame('Overtime totals wrong', DB::table('payroll_approvals')->where('payroll_id', $rejected)->where('step_order', 1)->value('remarks'));
        $this->assertSame('draft', DB::table('payroll')->where('id', $rejected)->value('status'));
        $this->assertNull($this->row('finance', 'Payroll Approval'));

        $steps($approved = $payroll());
        $this->click('finance', $this->row('finance', 'Payroll Approval'), 'approve');
        $this->assertSame('approved', DB::table('payroll_approvals')->where('payroll_id', $approved)->where('step_order', 1)->value('status'));
        $this->assertNotNull($this->row('hr', 'Payroll Approval'), 'step 2 is now HR\'s');
    }

    public function test_learning_request_hold_and_deny_for_the_ld_manager_only()
    {
        $ld = $this->emp('hr.manager');
        DB::table('resort_positions')->where('id', $ld->Position_id)->update(['position_title' => 'L&D Manager']);
        $category = DB::table('learning_categories')->insertGetId(['resort_id' => $this->resortId, 'category' => 'Service', 'color' => '#000', 'created_at' => now(), 'updated_at' => now()]);
        $program = DB::table('learning_programs')->insertGetId(['resort_id' => $this->resortId, 'name' => 'Guest Service Basics', 'description' => '-',
            'learning_category_id' => $category, 'target_audience' => 'All', 'frequency' => 'Once', 'delivery_mode' => 'Classroom', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('learning_requests')->insertGetId(['resort_id' => $this->resortId, 'learning_id' => $program, 'learning_manager_id' => $ld->id,
            'status' => 'Pending', 'created_by' => $this->emp('hod.fnb')->Admin_Parent_id, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertNull($this->row('hod.fnb', 'Learning Request'));
        $row = $this->row('hr.manager', 'Learning Request', 'Guest Service Basics');
        $this->assertNotNull($row);
        $this->click('hr.manager', $row, 'hold', 'Next quarter');
        $this->assertSame(['On Hold', 'Next quarter'], array_values((array) DB::table('learning_requests')->where('id', $id)->first(['status', 'rejection_reason'])));

        $this->click('hr.manager', $this->row('hr.manager', 'Learning Request', 'Guest Service Basics'), 'reject', 'No budget');
        $this->assertSame('Denied', DB::table('learning_requests')->where('id', $id)->value('status'), "Reject maps to the module's Denied");
        $this->assertNull($this->row('hr.manager', 'Learning Request', 'Guest Service Basics'));
    }

    public function test_visa_payment_request_reject_for_finance()
    {
        $id = DB::table('payment_requests')->insertGetId(['resort_id' => $this->resortId, 'Requestd_id' => 'PR-TEST-1', 'Request_date' => now()->toDateString(),
            'Status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertNull($this->row('hod.housekeeping', 'Visa Payment Request'));
        $row = $this->row('finance', 'Visa Payment Request', 'PR-TEST-1');
        $this->assertNotNull($row);
        $this->assertStringNotContainsString('btnIcon-skyblue me-2" data-req_id', strstr($row['action'], 'view-btn', true) ?: '', 'no approve button');

        $this->click('finance', $row, 'reject', 'Wrong fee amounts');
        $this->assertSame(['Rejact', 'Wrong fee amounts'], array_values((array) DB::table('payment_requests')->where('id', $id)->first(['Status', 'Reason'])),
            'stored as the enum value, not blanked');
        $this->assertNull($this->row('finance', 'Visa Payment Request', 'PR-TEST-1'));
    }

    public function test_budgets_finance_forwards_the_year_and_gm_approves_or_revises_per_department()
    {
        $depts = DB::table('resort_departments')->where('resort_id', $this->resortId)->pluck('id', 'name');
        foreach ($depts as $deptId) {
            DB::table('manning_responses')->insert(['resort_id' => $this->resortId, 'dept_id' => $deptId, 'year' => 2027, 'status' => 'submitted',
                'employment_type' => 'Permanent', 'budget_process_status' => 'Finance', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->assertNull($this->row('gm', 'Budget Approval'), 'GM waits for Finance');
        $row = $this->row('finance', 'Budget Approval', 'Permanent budget 2027');
        $this->assertNotNull($row);
        $this->assertStringContainsString(count($depts) . ' department', $row['name']);
        $this->click('finance', $row, 'approve');
        $this->assertSame(count($depts), DB::table('manning_responses')->where('resort_id', $this->resortId)->where('year', 2027)->where('budget_process_status', 'GM')->count());
        $this->assertNull($this->row('finance', 'Budget Approval', 'Permanent budget 2027'));

        $kitchen = $this->row('gm', 'Budget Approval', 'Kitchen');
        $this->assertNotNull($kitchen);
        $this->click('gm', $kitchen, 'approve');
        $this->assertSame('Approved', DB::table('manning_responses')->where('resort_id', $this->resortId)->where('dept_id', $depts['Kitchen'])->value('budget_process_status'));

        $this->click('gm', $this->row('gm', 'Budget Approval', 'Housekeeping'), 'reject', 'Too many line staff');
        $this->assertSame('draft', DB::table('manning_responses')->where('resort_id', $this->resortId)->where('dept_id', $depts['Housekeeping'])->value('status'), 'sent back to the department');
        $this->assertNull($this->row('gm', 'Budget Approval', 'Housekeeping'));
        $this->assertNull($this->row('gm', 'Budget Approval', 'Kitchen'));
    }

    public function test_info_update_has_a_view_and_approve_works()
    {
        $staff = $this->emp('employee');
        $id = DB::table('employee_info_update_request')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $staff->id, 'title' => 'Phone change',
            'info_payload' => json_encode(['personal_phone' => '+960 7111111']), 'status' => 'Pending', 'created_by' => $staff->Admin_Parent_id,
            'modified_by' => $staff->Admin_Parent_id, 'created_at' => now(), 'updated_at' => now()]);
        $row = $this->row('hr', 'Info Update Request', $staff->resortAdmin->full_name);
        $this->assertNotNull($row);
        preg_match('/<a href="([^"]+)" class="[^"]*view-modal-btn/', $row['action'], $m);
        $this->assertNotEmpty($m, 'eye icon present');
        $this->as('hr')->getJson(html_entity_decode($m[1]))->assertOk()->assertJsonPath('status', 'success');

        $this->click('hr', $row, 'approve');
        $this->assertNotSame('Pending', DB::table('employee_info_update_request')->where('id', $id)->value('status'));
        $this->assertNull($this->row('hr', 'Info Update Request', $staff->resortAdmin->full_name));
    }

    public function test_searching_another_resorts_employee_id_returns_nothing()
    {
        $otherPrefix = DB::table('employees')->where('resort_id', '!=', $this->resortId)->whereNotNull('Emp_id')->where('Emp_id', 'like', '%-%')
            ->value(DB::raw("SUBSTRING_INDEX(Emp_id, '-', 1)"));
        $this->assertNotNull($otherPrefix);
        $rows = $this->inbox('hr', ['search' => $otherPrefix . '-']);
        $this->assertSame([], array_values(array_filter($rows, fn ($r) => !str_starts_with((string) $r['emp_id'], 'DMX') && $r['emp_id'] !== '—')),
            'the ungrouped orWhereHas(Emp_id) no longer escapes the resort filter');
    }

    /** $absent is on approved leave today and handed their approvals to $delegate (how the app records delegation). */
    private function delegate(string $absent, string $delegate): void
    {
        DB::table('employees_leaves')->insert(['resort_id' => $this->resortId, 'emp_id' => $this->emp($absent)->id,
            'leave_category_id' => DB::table('leave_categories')->where('resort_id', $this->resortId)->where('leave_type', 'Annual Leave')->value('id'),
            'from_date' => now()->subDay()->toDateString(), 'to_date' => now()->addDays(3)->toDateString(), 'total_days' => 5, 'duration' => 'Full Day',
            'reason' => 'Holiday', 'status' => 'Approved', 'task_delegation' => $this->emp($delegate)->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_resignation_withdrawal_goes_hod_then_hr_and_reject_needs_a_reason()
    {
        $staff = $this->emp('employee');
        DB::table('employees')->where('id', $staff->id)->update(['status' => 'Offboarding']);
        $id = DB::table('employee_resignation')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $staff->id, 'reason' => 'Relocation',
            'resignation_date' => now()->toDateString(), 'hod_id' => $this->emp('hod.housekeeping')->id, 'hr_id' => $this->emp('hr')->id,
            'status' => 'Approved', 'hod_status' => 'Approved', 'hr_status' => 'Approved', 'withdraw_hod_status' => 'Pending', 'withdraw_hr_status' => 'Pending',
            'created_at' => now(), 'updated_at' => now()]);
        $name = $staff->resortAdmin->full_name;

        $this->assertNull($this->row('hr', 'Resignation Withdrawal', $name), 'HR waits for the HOD');
        $row = $this->row('hod.housekeeping', 'Resignation Withdrawal', $name);
        $this->assertNotNull($row);
        $this->assertStringNotContainsString('On-Hold', $row['action'], 'no hold for withdrawals');
        $this->click('hod.housekeeping', $row, 'approve');
        $this->assertSame('Approved', DB::table('employee_resignation')->where('id', $id)->value('withdraw_hod_status'));
        $this->assertNull($this->row('hod.housekeeping', 'Resignation Withdrawal', $name));

        $this->click('hr', $this->row('hr', 'Resignation Withdrawal', $name), 'reject', 'Replacement already hired');
        $r = DB::table('employee_resignation')->where('id', $id)->first();
        $this->assertSame(['Rejected', 'Replacement already hired'], [$r->withdraw_hr_status, $r->withdraw_rejected_reason]);
        $this->assertSame('Offboarding', DB::table('employees')->where('id', $staff->id)->value('status'), 'a rejected withdrawal leaves the employee Offboarding');
        $this->assertNull($this->row('hr', 'Resignation Withdrawal', $name));
    }

    public function test_delegates_can_act_on_payroll_budget_and_learning()
    {
        // Payroll: the Finance Director is away, the Finance Manager covers step 1.
        $pid = DB::table('payroll')->insertGetId(['resort_id' => $this->resortId, 'start_date' => '2026-09-01', 'end_date' => '2026-09-30',
            'total_payroll' => 1000, 'total_employees' => 100, 'draft_date' => now(), 'payment_date' => now(), 'status' => 'pending_approval',
            'created_at' => now(), 'updated_at' => now()]);
        foreach ([1 => 'Finance EXCOM', 2 => 'HR EXCOM', 3 => 'GM'] as $order => $title) {
            DB::table('payroll_approvals')->insert(['payroll_id' => $pid, 'resort_id' => $this->resortId, 'step_order' => $order, 'role_title' => $title,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->assertNull($this->row('finance.manager', 'Payroll Approval'), 'not without a delegation');
        $this->delegate('finance', 'finance.manager');
        $this->click('finance.manager', $this->row('finance.manager', 'Payroll Approval'), 'approve');
        $this->assertSame('approved', DB::table('payroll_approvals')->where('payroll_id', $pid)->where('step_order', 1)->value('status'));
        $this->as('finance.manager')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->postJson(route('payroll.approval.status'), ['payroll_id' => $pid])->assertJsonPath('user_approval_step', 1);

        // Budget: the GM is away, the Resident Manager (EXCOM) covers the GM step.
        $kitchen = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'Kitchen')->value('id');
        $bid = DB::table('manning_responses')->insertGetId(['resort_id' => $this->resortId, 'dept_id' => $kitchen, 'year' => 2027, 'status' => 'submitted',
            'employment_type' => 'Permanent', 'budget_process_status' => 'GM', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertNull($this->row('excom', 'Budget Approval', 'Kitchen'));
        $this->delegate('gm', 'excom');
        $this->click('excom', $this->row('excom', 'Budget Approval', 'Kitchen'), 'approve');
        $this->assertSame('Approved', DB::table('manning_responses')->where('id', $bid)->value('budget_process_status'));

        // Learning: the L&D Manager is away, the F&B HOD covers.
        $ld = $this->emp('hr.manager');
        DB::table('resort_positions')->where('id', $ld->Position_id)->update(['position_title' => 'L&D Manager']);
        $cat = DB::table('learning_categories')->insertGetId(['resort_id' => $this->resortId, 'category' => 'Service', 'color' => '#000', 'created_at' => now(), 'updated_at' => now()]);
        $prog = DB::table('learning_programs')->insertGetId(['resort_id' => $this->resortId, 'name' => 'Wine Basics', 'description' => '-', 'learning_category_id' => $cat,
            'target_audience' => 'All', 'frequency' => 'Once', 'delivery_mode' => 'Classroom', 'created_at' => now(), 'updated_at' => now()]);
        $lid = DB::table('learning_requests')->insertGetId(['resort_id' => $this->resortId, 'learning_id' => $prog, 'learning_manager_id' => $ld->id,
            'status' => 'Pending', 'created_by' => $this->emp('hod.kitchen')->Admin_Parent_id, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertNull($this->row('hod.fnb', 'Learning Request', 'Wine Basics'));
        $this->delegate('hr.manager', 'hod.fnb');
        $this->click('hod.fnb', $this->row('hod.fnb', 'Learning Request', 'Wine Basics'), 'approve');
        $this->assertSame('Approved', DB::table('learning_requests')->where('id', $lid)->value('status'));
    }

    public function test_salary_advance_finance_stage_accepts_a_delegate()
    {
        $staff = $this->emp('employee');
        $id = DB::table('payroll_advance')->insertGetId(['employee_id' => $staff->id, 'resort_id' => $this->resortId, 'request_type' => 'Salary Advance',
            'request_date' => now()->toDateString(), 'pourpose' => 'Family', 'request_amount' => 300, 'hr_status' => 'Approved', 'finance_status' => 'Pending',
            'gm_status' => 'Pending', 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payroll_advance_guarantor')->insert(['payroll_advance_id' => $id, 'guarantor_id' => $this->emp('supervisor')->id, 'status' => 'Approved',
            'created_at' => now(), 'updated_at' => now()]);
        $name = $staff->resortAdmin->full_name;

        $this->assertNull($this->row('hod.fnb', 'Payroll Advance', $name));
        $this->delegate('finance', 'hod.fnb'); // the F&B HOD covers for the Finance Director
        $row = $this->row('hod.fnb', 'Payroll Advance', $name);
        $this->assertNotNull($row, 'the delegate sees the Finance stage');
        $this->click('hod.fnb', $row, 'approve');
        $this->assertSame('Approved', DB::table('payroll_advance')->where('id', $id)->value('finance_status'), 'and the endpoint accepts them (was: never matched)');
    }
}
