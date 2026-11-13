<?php

namespace Tests\Feature;

use App\Services\Wisdom\DataCatalog;
use App\Services\Wisdom\WisdomAccess;
use App\Services\Wisdom\WisdomTools;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** WAI query_data: answers from the catalog only, inside the user's resort / access, never runs text from the AI as SQL. */
class WisdomDataCatalogTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        // The read-only connection must see this test's (uncommitted) rows.
        DB::connection('mysql_readonly')->setPdo(DB::connection()->getPdo());
    }

    private function full(): array
    {
        return ['tier' => 'full', 'resort_id' => $this->resortId, 'can_db' => true, 'can_payroll' => true];
    }

    public function test_it_answers_counts_and_lists_from_the_users_resort_only()
    {
        $active = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')->count();
        $r = DataCatalog::query(['dataset' => 'employees', 'metrics' => [['fn' => 'count']], 'filters' => [['field' => 'status', 'op' => '=', 'value' => 'Active']]], $this->full());
        $this->assertSame($active, (int) $r['rows'][0]['count']);

        // another resort's employees never appear, even though the query has no resort filter of its own
        $other = DB::table('employees')->where('resort_id', '!=', $this->resortId)->whereNotNull('Emp_id')->value('Emp_id');
        $ids = array_column(DataCatalog::query(['dataset' => 'employees', 'fields' => ['employee_id'], 'limit' => 200], $this->full())['rows'], 'employee_id');
        $this->assertNotEmpty($ids);
        $this->assertNotContains($other, $ids);
        $this->assertSame($active > 0, (bool) array_filter($ids));

        // summaries by a joined field
        $byDept = DataCatalog::query(['dataset' => 'employees', 'group_by' => ['department'], 'filters' => [['field' => 'status', 'op' => '=', 'value' => 'Active']]], $this->full());
        $this->assertSame($active, array_sum(array_map('intval', array_column($byDept['rows'], 'count'))));
    }

    public function test_every_table_is_resort_filtered_and_a_scoped_user_only_sees_their_departments()
    {
        [$q] = DataCatalog::build(['dataset' => 'leave_requests', 'fields' => ['name', 'department', 'leave_type']], $this->full() + ['dept_ids' => [7]]);
        $sql = $q->toSql();
        foreach (['l', 'e', 'ra', 'd', 'c'] as $alias) {
            $this->assertStringContainsString("`{$alias}`.`resort_id` = ?", $sql, "table {$alias} is resort-filtered");
        }
        $this->assertStringContainsString('`e`.`Dept_id` in (?)', $sql);
        $this->assertSame(5, count(array_keys($q->getBindings(), $this->resortId, true)));
    }

    public function test_pay_fields_follow_the_tier_and_unknown_names_and_ops_are_refused()
    {
        $gm = ['tier' => 'gm', 'resort_id' => $this->resortId, 'can_db' => true];
        $this->assertArrayHasKey('error', DataCatalog::query(['dataset' => 'employees', 'fields' => ['basic_salary']], $gm));
        $this->assertArrayHasKey('error', DataCatalog::query(['dataset' => 'employees', 'metrics' => [['fn' => 'sum', 'field' => 'basic_salary']]], $gm));
        $this->assertArrayNotHasKey('basic_salary', DataCatalog::describe($gm)['datasets'][0]['fields']);
        $this->assertArrayNotHasKey('error', DataCatalog::query(['dataset' => 'employees', 'metrics' => [['fn' => 'sum', 'field' => 'basic_salary']]], $this->full()));

        foreach ([
            ['dataset' => 'resort_admins', 'fields' => ['password']],                                    // not a dataset
            ['dataset' => 'employees', 'fields' => ['passport_number']],                                 // column not in the catalog
            ['dataset' => 'employees', 'fields' => ['name`, (select password from resort_admins) as `x']],
            ['dataset' => 'employees', 'fields' => ['employee_id'], 'filters' => [['field' => 'status', 'op' => '= 1 OR 1 =', 'value' => 1]]],
            ['dataset' => 'employees', 'fields' => ['employee_id'], 'sort' => [['by' => 'resort_id']]],
            ['dataset' => 'employees', 'metrics' => [['fn' => 'sleep', 'field' => 'employee_id']]],
            ['dataset' => 'employees', 'fields' => ['employee_id'], 'filters' => [['field' => 'joining_date', 'op' => '>=', 'value' => "2020-01-01' OR '1'='1"]]],
        ] as $bad) {
            $this->assertArrayHasKey('error', DataCatalog::query($bad, $this->full()), json_encode($bad));
        }

        // a value is always a bound value: SQL in it just matches nothing
        $r = DataCatalog::query(['dataset' => 'employees', 'fields' => ['employee_id'], 'filters' => [['field' => 'nationality', 'op' => '=', 'value' => "x' OR '1'='1"]]], $this->full());
        $this->assertSame(0, $r['row_count']);
    }

    public function test_the_tools_are_offered_to_data_tiers_only()
    {
        $names = fn ($ctx) => array_map(fn ($t) => $t['function']['name'], WisdomTools::definitions($ctx));
        $this->assertContains('query_data', $names($this->full()));
        $this->assertNotContains('query_data', $names(['tier' => 'policy', 'resort_id' => $this->resortId, 'can_db' => false]));
        $this->assertArrayHasKey('error', WisdomTools::execute('query_data', ['dataset' => 'employees', 'fields' => ['employee_id']], ['tier' => 'policy', 'resort_id' => $this->resortId, 'can_db' => false]));
        $this->assertArrayHasKey('rows', WisdomTools::execute('query_data', ['dataset' => 'employees', 'fields' => ['employee_id'], 'limit' => 1], $this->full()));
    }

    private function ctxFor(string $login): ?array
    {
        $this->as($login);
        return WisdomAccess::context();
    }

    public function test_only_the_hr_departments_director_and_hod_have_wisdom_ai()
    {
        // decision 2026-11-13: HR Director (EXCOM) and HR HOD only
        foreach (['hr', 'hr.manager'] as $login) {
            $this->assertSame(WisdomAccess::TIER_FULL, $this->ctxFor($login)['tier'] ?? null, $login);
        }
        foreach (['gm', 'finance', 'finance.manager', 'hod.kitchen', 'manager', 'supervisor', 'employee'] as $login) {
            $this->assertNull($this->ctxFor($login), "{$login} has no Wisdom AI");
            $this->postJson(route('resort.wisdom.chat'), ['message' => 'How many staff do we have?'])->assertForbidden();
        }
        // the resort master admin (not an employee) has none either
        $master = \App\Models\ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->first();
        if ($master) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($master, 'resort-admin');
            $this->assertNull(WisdomAccess::context());
        }
    }

    public function test_department_scoping_in_the_engine_still_holds()
    {
        // kept for when department-level access is opened again: a scoped context only ever sees its departments
        $dept = (int) $this->emp('hod.kitchen')->Dept_id;
        $ctx = ['tier' => 'leader', 'resort_id' => $this->resortId, 'dept_ids' => [$dept]];
        $rows = DataCatalog::query(['dataset' => 'employees', 'group_by' => ['department']], $ctx)['rows'];
        $this->assertSame([DB::table('resort_departments')->where('id', $dept)->value('name')], array_column($rows, 'department'));
        $this->assertArrayHasKey('error', DataCatalog::query(['dataset' => 'employees', 'fields' => ['basic_salary']], $ctx));
    }

    public function test_payslip_totals_match_the_stored_payroll_lines()
    {
        $expected = (float) DB::table('payroll_reviews as pr')->join('payroll as p', 'p.id', '=', 'pr.payroll_id')->where('p.resort_id', $this->resortId)->sum('pr.net_salary');
        $r = WisdomTools::execute('query_data', ['dataset' => 'payslips', 'metrics' => [['fn' => 'sum', 'field' => 'net_salary']]], $this->full());
        $this->assertEqualsWithDelta($expected, (float) $r['rows'][0]['sum_net_salary'], 0.01);
    }

    public function test_a_table_without_a_resort_column_is_reached_only_through_its_resort_filtered_parent()
    {
        [$q] = DataCatalog::build(['dataset' => 'payslips', 'metrics' => [['fn' => 'count']]], $this->full());
        $this->assertStringContainsString('inner join `payroll` as `p` on `p`.`id` = `pr`.`payroll_id` and `p`.`resort_id` = ?', $q->toSql());
        [$q] = DataCatalog::build(['dataset' => 'training_participants', 'metrics' => [['fn' => 'count']]], $this->full());
        $this->assertStringContainsString('inner join `training_schedules` as `ts` on `ts`.`id` = `tpp`.`training_schedule_id` and `ts`.`resort_id` = ?', $q->toSql());

        // a catalog entry that forgets its resort filter is refused, not run
        config(['wisdom_catalog.datasets.broken' => ['module' => 'X', 'description' => 'x', 'table' => 'payroll_reviews', 'alias' => 'pr', 'resort' => null,
            'fields' => ['net' => ['col' => 'pr.net_salary', 'type' => 'number']]]]);
        $this->expectException(\LogicException::class);
        DataCatalog::build(['dataset' => 'broken', 'fields' => ['net']], $this->full());
    }

    public function test_every_dataset_runs_for_every_tier()
    {
        foreach (['full', 'gm', 'finance'] as $tier) {
            foreach (DataCatalog::datasets(['tier' => $tier, 'resort_id' => $this->resortId]) as $name => $ds) {
                $r = DataCatalog::query(['dataset' => $name, 'fields' => array_keys($ds['fields']), 'limit' => 3], ['tier' => $tier, 'resort_id' => $this->resortId]);
                $this->assertArrayNotHasKey('error', $r, "{$tier} {$name}: " . ($r['error'] ?? ''));
            }
        }
    }

    public function test_leave_balances_come_from_the_leave_engine_with_a_settlement_liability()
    {
        $emp = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')->first();
        $engine = collect(\App\Helpers\Common::getEmployeeLeaveBalance($emp->id, $this->resortId))->keyBy('leave_type');
        $this->assertNotEmpty($engine, 'demo employee has leave allocations');

        $r = DataCatalog::query(['dataset' => 'leave_balances', 'fields' => ['leave_type', 'balance', 'daily_rate_mvr', 'liability_mvr'],
            'filters' => [['field' => 'employee_id', 'op' => '=', 'value' => $emp->Emp_id]]], $this->full());
        $this->assertCount($engine->count(), $r['rows']);
        $rate = (float) (DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('DollertoMVR') ?: 15.42);
        $daily = round((strtoupper((string) $emp->basic_salary_currency) === 'USD' ? $emp->basic_salary * $rate : $emp->basic_salary) / 30, 2);
        foreach ($r['rows'] as $row) {
            $this->assertEquals($engine[$row['leave_type']]['balance'], $row['balance']);
            $this->assertEqualsWithDelta(round(max($row['balance'], 0) * $daily, 2), $row['liability_mvr'], 0.01);
        }

        // summaries, filters and unknown names behave like table datasets
        $byDept = DataCatalog::query(['dataset' => 'leave_balances', 'group_by' => ['department'], 'metrics' => [['fn' => 'sum', 'field' => 'liability_mvr']],
            'filters' => [['field' => 'balance', 'op' => '>', 'value' => 0]], 'sort' => [['by' => 'sum_liability_mvr', 'dir' => 'desc']]], $this->full());
        $this->assertArrayNotHasKey('error', $byDept);
        $this->assertArrayHasKey('error', DataCatalog::query(['dataset' => 'leave_balances', 'fields' => ['basic_salary']], $this->full()));
        $this->assertArrayHasKey('error', DataCatalog::query(['dataset' => 'leave_balances', 'fields' => ['name'], 'filters' => [['field' => 'balance', 'op' => 'like', 'value' => 1]]], $this->full()));

        // pay figures follow sensitivity; a department scope is applied by the source itself
        $this->assertArrayHasKey('error', DataCatalog::query(['dataset' => 'leave_balances', 'fields' => ['liability_mvr']], ['tier' => 'gm', 'resort_id' => $this->resortId]));
        $dept = (int) $this->emp('hod.kitchen')->Dept_id;
        $scoped = DataCatalog::query(['dataset' => 'leave_balances', 'group_by' => ['department']], ['tier' => 'leader', 'resort_id' => $this->resortId, 'dept_ids' => [$dept]]);
        $this->assertSame([DB::table('resort_departments')->where('id', $dept)->value('name')], array_column($scoped['rows'], 'department'));
    }

    public function test_visa_spending_for_a_year_matches_the_paid_fees_and_deposits()
    {
        $year = now()->year;
        $wp = (float) DB::table('work_permits')->where('resort_id', $this->resortId)->where('Status', 'Paid')
            ->whereYear(DB::raw('COALESCE(Payment_Date, Due_Date)'), $year)->sum('Amt');
        $r = DataCatalog::query(['dataset' => 'work_permit_fees', 'metrics' => [['fn' => 'sum', 'field' => 'amount']], 'filters' => [
            ['field' => 'status', 'op' => '=', 'value' => 'Paid'], ['field' => 'paid_or_due_date', 'op' => 'between', 'value' => ["{$year}-01-01", "{$year}-12-31"]]]], $this->full());
        $this->assertEqualsWithDelta($wp, (float) $r['rows'][0]['sum_amount'], 0.01);

        $dep = (float) DB::table('total_expensess_since_joings')->where('resort_id', $this->resortId)->whereYear('Date', $year)->sum('Deposit_Amt');
        $r = DataCatalog::query(['dataset' => 'visa_deposits', 'metrics' => [['fn' => 'sum', 'field' => 'deposit_amount']],
            'filters' => [['field' => 'deposit_date', 'op' => '>=', 'value' => "{$year}-01-01"]]], $this->full());
        $this->assertEqualsWithDelta($dep, (float) $r['rows'][0]['sum_deposit_amount'], 0.01);
    }

    public function test_probation_counts_people_whose_review_is_overdue()
    {
        $id = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')->value('id');
        DB::table('employees')->where('id', $id)->update(['probation_status' => 'Active', 'probation_end_date' => now()->subMonth()->toDateString()]);
        $r = WisdomTools::execute('get_probation_overview', [], $this->full());
        $expected = DB::table('employees')->where('resort_id', $this->resortId)->whereIn('status', ['Active', 'Onboarding'])
            ->whereIn('probation_status', ['Active', 'Extended'])->count();
        $this->assertSame($expected, $r['on_probation']);
        $this->assertGreaterThanOrEqual(1, $r['review_overdue']);
    }

    public function test_vacancy_meaning_is_in_the_data_not_left_to_the_model()
    {
        $open = DB::table('vacancies')->where('Resort_id', $this->resortId)->whereIn('status', ['Active', 'Open'])
            ->whereDate('required_starting_date', '<', today())->pluck('id')->sort()->values()->all();
        $r = DataCatalog::query(['dataset' => 'vacancies', 'fields' => ['vacancy_id', 'applicant_count'], 'limit' => 200,
            'filters' => [['field' => 'is_open', 'op' => '=', 'value' => 1], ['field' => 'days_past_start', 'op' => '>', 'value' => 0]]], $this->full());
        $this->assertSame($open, collect($r['rows'])->pluck('vacancy_id')->map(fn ($v) => (int) $v)->sort()->values()->all());
        foreach ($r['rows'] as $row) {
            $this->assertSame(DB::table('applicant_form_data')->where('resort_id', $this->resortId)->where('Parent_v_id', $row['vacancy_id'])->count(), (int) $row['applicant_count']);
        }
    }

    public function test_expat_documents_follow_the_xpat_screen_rules_and_only_a_visa_blocks_work()
    {
        $rows = collect(DataCatalog::query(['dataset' => 'expat_documents', 'limit' => 200, 'fields' => ['employee_id', 'visa_expiry', 'work_permit_expiry', 'insurance_expiry', 'medical_expiry', 'missing_count', 'can_work_legally', 'visa_days_left']], $this->full())['rows']);
        $expats = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')
            ->where(fn ($w) => $w->whereNull('nationality')->orWhere('nationality', '!=', 'Maldivian'))->get(['id', 'Emp_id']);
        $this->assertSame($expats->count(), $rows->count());
        foreach ($expats as $e) {
            $row = $rows->firstWhere('employee_id', $e->Emp_id);
            $ins = \App\Helpers\Common::visaNextDue(\App\Models\EmployeeInsurance::class, $this->resortId, $e->id, 'insurance_end_date', 'desc');
            if ($ins) {
                $this->assertSame(\Carbon\Carbon::parse($ins)->toDateString(), $row['insurance_expiry']);
            }
            $wp = \App\Helpers\Common::visaNextDue(\App\Models\WorkPermit::class, $this->resortId, $e->id, 'Due_Date', 'asc');
            if ($wp) {
                $this->assertSame(\Carbon\Carbon::parse($wp)->toDateString(), $row['work_permit_expiry']);
            }
            $expected = $row['visa_expiry'] === null ? 'unknown (no visa on record)' : ($row['visa_expiry'] >= today()->toDateString() ? 'yes' : 'no (visa expired)');
            $this->assertSame($expected, $row['can_work_legally'], 'only the visa decides');
        }

        $types = collect(DataCatalog::query(['dataset' => 'immigration_renewals', 'group_by' => ['document', 'blocks_work']], $this->full())['rows']);
        foreach ($types as $t) {
            $this->assertSame(in_array($t['document'], ['Visa', 'Work Permit'], true) ? 'yes' : 'no', $t['blocks_work']);
        }
    }

    public function test_tools_a_dataset_replaces_are_not_offered_but_still_run()
    {
        $names = array_map(fn ($t) => $t['function']['name'], WisdomTools::definitions($this->full()));
        foreach (config('wisdom_catalog.replaces_tools') as $hidden) {
            $this->assertNotContains($hidden, $names);
        }
        $this->assertContains('query_data', $names);
        $this->assertArrayNotHasKey('error', WisdomTools::execute('get_headcount', [], $this->full()));
        // the dataset index the model sees names every dataset and its fields
        $desc = collect(WisdomTools::definitions($this->full()))->firstWhere('function.name', 'query_data')['function']['description'];
        $this->assertStringContainsString('- expat_documents (Visa):', $desc);
        $this->assertStringContainsString('visa_days_left', $desc);
    }

    public function test_employee_relations_survey_and_compliance_carry_their_meaning()
    {
        $emp = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')->first();
        $gid = DB::table('grivance_submission_models')->insertGetId(['resort_id' => $this->resortId, 'Employee_id' => $emp->id, 'Grivance_id' => 'GR-T1',
            'status' => 'pending', 'Priority' => 'High', 'Request_Identity_Disclosure' => 'No', 'created_at' => now()->subDays(30), 'updated_at' => now()]);
        DB::table('grivance_resoultion_time_line_models')->insert(['resort_id' => $this->resortId, 'HighPriority' => 2, 'MediumPriority' => 4, 'LowPriority' => 6, 'created_at' => now(), 'updated_at' => now()]);
        $row = collect(DataCatalog::query(['dataset' => 'grievance_cases', 'limit' => 200], $this->full())['rows'])->firstWhere('case_id', 'GR-T1');
        $this->assertNull($row['raised_by'], 'a confidential grievance never shows who raised it');
        $this->assertSame('yes', $row['confidential']);
        $this->assertSame(1, (int) $row['is_overdue'], 'open 30 days, high priority = 2-day limit');

        // survey: who responded, never names on an anonymous survey
        $sid = DB::table('parent_surveys')->insertGetId(['resort_id' => $this->resortId, 'Surevey_title' => 'T anon', 'Status' => 'OnGoing', 'survey_privacy_type' => 'Anonymous', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('survey_employees')->insert(['Parent_survey_id' => $sid, 'Emp_id' => $emp->id, 'emp_status' => 'yes', 'created_at' => now(), 'updated_at' => now()]);
        $r = DataCatalog::query(['dataset' => 'survey_participation', 'fields' => ['name', 'responded'], 'filters' => [['field' => 'survey_id', 'op' => '=', 'value' => $sid]]], $this->full());
        $this->assertSame([['name' => null, 'responded' => 'yes']], $r['rows']);

        // compliance: soft-deleted flags are not open issues
        DB::table('compliances')->insert([
            ['resort_id' => $this->resortId, 'employee_id' => $emp->id, 'compliance_breached_name' => 'T Rule', 'status' => 'Breached', 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['resort_id' => $this->resortId, 'employee_id' => $emp->id, 'compliance_breached_name' => 'T Rule', 'status' => 'Breached', 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        $c = DataCatalog::query(['dataset' => 'compliance_issues', 'metrics' => [['fn' => 'count'], ['fn' => 'count_distinct', 'field' => 'employee_rule']], 'filters' => [['field' => 'rule', 'op' => '=', 'value' => 'T Rule']]], $this->full());
        $this->assertSame(1, (int) $c['rows'][0]['count']);

        // the engine: no field list = every field; true/false work on yes/no fields
        $all = DataCatalog::query(['dataset' => 'grievance_cases', 'filters' => [['field' => 'confidential', 'op' => '=', 'value' => true]]], $this->full());
        $this->assertArrayNotHasKey('error', $all);
        $this->assertArrayHasKey('is_overdue', $all['rows'][0]);
    }
}
