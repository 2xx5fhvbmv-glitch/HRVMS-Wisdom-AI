<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Support\Demo\Demo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Demo ENV slice 5a (workforce planning): the HR login opens the Workforce Planning screens and each
 * one shows the seeded data — budgeted / filled / vacant seats, the three Manning tabs, both cost
 * configurations, and a priced vacant seat in the budget.
 */
class DemoWorkforceTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST99',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMT']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Mail::fake();
        Http::fake(); // the AI staffing service is never called from a test
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->post(route('resort.login'), ['email' => 'hr@demo.thewisdom.ai', 'password' => 'Demo#Pass2026'])->assertOk();
    }

    public function test_the_dashboard_shows_this_years_budgeted_filled_and_vacant_seats()
    {
        $seats = $this->get(route('resort.workforceplan.dashboard'))->assertOk()->viewData('manning_response');
        $this->assertTrue($seats->using_manning_data);
        $this->assertSame((int) date('Y'), (int) $seats->manning_year);
        $this->assertGreaterThan(0, $seats->total_vacant_count);
        $this->assertSame($seats->total_budgeted_employees, $seats->total_filled_positions_count + $seats->total_vacant_count);
    }

    public function test_the_manning_tabs_show_permanent_casual_and_intern_seats()
    {
        $this->get(route('resort.budget.manning', ['category_view' => 'permanent']))->assertOk()->assertSee('Vacant')->assertSee('Sommelier');
        $this->get(route('resort.budget.manning', ['category_view' => 'nonpermanent']))->assertOk()
            ->assertSee('Casual Room Attendant')->assertSee('Culinary Intern');
        $this->get(route('resort.budget.manning', ['category_view' => 'all']))->assertOk()->assertSee('Casual Waiter');
    }

    public function test_cost_configuration_lists_both_sets_of_lines()
    {
        $this->assertSame(23, (int) $this->getJson(route('resort.budget.costlist'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('recordsTotal'));
        $this->assertSame(5, (int) $this->getJson(route('resort.budget.nonpermanent.costlist'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('recordsTotal'));
        $ramadan = DB::table('resort_budget_costs')->where('resort_id', $this->resortId)->where('particulars', 'Ramadan Bonus')->first();
        $this->assertSame('MVR', $ramadan->amount_unit);
    }

    public function test_the_budget_prices_a_seat_nobody_holds_yet()
    {
        $sommelier = DB::table('resort_positions')->where('resort_id', $this->resortId)->where('position_title', 'Sommelier')->value('id');
        $totals = $this->getJson(route('resort.budget.hierarchy.all-totals', ['year' => date('Y'), 'employment_type' => 'Permanent']))
            ->assertOk()->json('positions');
        $this->assertGreaterThan(0, $totals[$sommelier]['total'], 'an empty, budgeted position still costs its salary');
        $this->assertGreaterThan(0, array_sum(array_column($totals, 'total')));
    }
}
