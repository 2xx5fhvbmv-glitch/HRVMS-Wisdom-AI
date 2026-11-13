<?php

namespace Tests\Feature;

use App\Services\Wisdom\DataCatalog;
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
}
