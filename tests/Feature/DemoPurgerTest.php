<?php

namespace Tests\Feature;

use App\Support\Demo\Demo;
use App\Support\Demo\DemoPurger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The demo reset deletes one resort's rows inside the shared database, so the
 * purger is tested by really purging existing resorts (rolled back afterwards)
 * and checking nothing outside the plan moved.
 */
class DemoPurgerTest extends TestCase
{
    use DatabaseTransactions;

    private function counts(): array
    {
        $counts = [];
        foreach (DB::select('SELECT TABLE_NAME t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = "BASE TABLE"') as $t) {
            $counts[$t->t] = DB::table($t->t)->count();
        }
        return $counts;
    }

    private function resortColumnTables(): array
    {
        return collect(DB::select('SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND LOWER(COLUMN_NAME) = "resort_id" AND DATA_TYPE IN ("int","bigint","mediumint","smallint","tinyint")'))
            ->reject(fn ($r) => $r->t === 'resorts')->pluck('c', 't')->all();
    }

    public function test_schema_is_fully_classified()
    {
        $resortId = DB::table('resorts')->value('id');
        $this->assertSame([], (new DemoPurger($resortId))->audit());
    }

    public function test_purge_removes_exactly_the_planned_rows_of_one_resort()
    {
        foreach (DB::table('resorts')->whereIn('id', [25, 27])->pluck('id') as $resortId) {
            $before = $this->counts();
            $others = [];
            foreach ($this->resortColumnTables() as $table => $col) {
                $others[$table] = DB::table($table)->where($col, '!=', $resortId)->count();
            }

            $purger = new DemoPurger($resortId);
            $plan = $purger->plan();
            $this->assertNotEmpty($plan);
            $deleted = $purger->purge();
            $this->assertEquals($plan, $deleted, "resort {$resortId}: deleted rows match the plan");

            $after = $this->counts();
            foreach ($before as $table => $n) {
                $this->assertSame($n - ($plan[$table] ?? 0), $after[$table], "resort {$resortId}: {$table} lost only planned rows");
            }
            foreach ($this->resortColumnTables() as $table => $col) {
                $this->assertSame(0, DB::table($table)->where($col, $resortId)->count(), "resort {$resortId}: nothing left in {$table}");
                $this->assertSame($others[$table], DB::table($table)->where($col, '!=', $resortId)->count(), "resort {$resortId}: other resorts' {$table} untouched");
            }
            $this->assertTrue(DB::table('resorts')->where('id', $resortId)->exists(), 'the resort row itself is kept');
        }
    }

    public function test_a_row_of_another_resort_pointing_in_blocks_the_whole_purge()
    {
        // Resort 28 has employees linked to one of resort 26's logins (existing data):
        // purging 26 must fail and leave everything as it was, not delete resort 28's staff.
        if (!DB::table('employees')->where('resort_id', '!=', 26)
            ->whereIn('Admin_Parent_id', DB::table('resort_admins')->where('resort_id', 26)->select('id'))->exists()) {
            $this->markTestSkipped('No cross-resort reference in this database.');
        }
        $before = $this->counts();
        try {
            (new DemoPurger(26))->purge();
            $this->fail('Purge should have been refused.');
        } catch (\Throwable $e) {
            $this->assertStringContainsStringIgnoringCase('foreign key', $e->getMessage());
        }
        $this->assertSame($before, $this->counts());
    }

    public function test_reset_guard_only_accepts_the_demo_resort()
    {
        config(['demo.enabled' => false]);
        $this->assertGuardRefuses('Demo mode is off');

        config(['demo.enabled' => true, 'demo.resort_code' => 'NOSUCHCODE']);
        $this->assertGuardRefuses('does not exist');

        // A real resort's code, but without the demo email → refused.
        config(['demo.resort_code' => DB::table('resorts')->where('id', 26)->value('resort_id')]);
        $this->assertGuardRefuses('not the demo email');

        // Demo code + demo email but far too many employees → refused.
        $id = DB::table('resorts')->insertGetId(['resort_name' => 'Demo ENV', 'resort_id' => 'DEMOTEST01', 'resort_email' => config('demo.resort_email'),
            'resort_it_email' => 'it@demo.test', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        config(['demo.resort_code' => 'DEMOTEST01', 'demo.max_employees' => -1]);
        $this->assertGuardRefuses('more than a demo ever has');

        config(['demo.max_employees' => 400]);
        $this->assertSame($id, Demo::assertSafeToReset()->id);
    }

    private function assertGuardRefuses(string $reason): void
    {
        Demo::refresh();
        try {
            Demo::assertSafeToReset();
            $this->fail("Guard should refuse ({$reason}).");
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString($reason, $e->getMessage());
        }
    }
}
