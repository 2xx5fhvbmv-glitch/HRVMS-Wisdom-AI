<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/**
 * Workforce Planning (remaining-work list, 2026-11-03): Request Manning is HR/GM only and closes for the
 * rest of the year once next year's budget is GM-approved; a manning response's vacancies are worked out
 * server-side, never taken from the form.
 */
class ManningRequestTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function cycles(): int
    {
        return DB::table('resorts_parent_notifications')->where('resort_id', $this->resortId)->count();
    }

    public function test_only_hr_or_gm_can_start_a_manning_cycle()
    {
        foreach (['manager', 'hod.kitchen'] as $login) {
            $this->as($login)->postJson(route('resort.manning.notification'), ['manningRequest' => 'Please send'])->assertForbidden();
        }
        $this->assertSame(0, $this->cycles(), 'a refused request starts nothing');

        $this->as('hr')->postJson(route('resort.manning.notification'), ['manningRequest' => 'Please send'])->assertJson(['success' => true]);
        $this->assertSame(1, $this->cycles());
    }

    public function test_request_closes_once_next_years_budget_is_gm_approved_and_reopens_next_year()
    {
        $next = now()->year + 1;
        $dept = DB::table('resort_departments')->where('resort_id', $this->resortId)->value('id');
        DB::table('manning_responses')->insert(['resort_id' => $this->resortId, 'dept_id' => $dept, 'year' => $next, 'status' => 'submitted',
            'employment_type' => 'Permanent', 'budget_process_status' => 'GM', 'created_at' => now(), 'updated_at' => now()]);

        $this->as('hr')->get(route('resort.workforceplan.dashboard'))->assertOk()->assertDontSee('budget has already been approved');

        DB::table('manning_responses')->where('resort_id', $this->resortId)->update(['budget_process_status' => 'Approved']);
        $this->as('hr')->get(route('resort.workforceplan.dashboard'))->assertOk()
            ->assertSee("The {$next} budget has already been approved. You cannot request again.");
        $this->as('hr')->postJson(route('resort.manning.notification'), ['manningRequest' => 'Again'])->assertStatus(409);
        $this->assertSame(0, $this->cycles());

        $this->travelTo(now()->addYear()->startOfYear());   // 1 Jan: now asking for the year after
        $this->as('hr')->postJson(route('resort.manning.notification'), ['manningRequest' => 'New year'])->assertJson(['success' => true]);
    }

    public function test_vacancies_are_headcount_minus_filled_whatever_the_form_sends()
    {
        $dept = $this->emp('hod.kitchen')->Dept_id;
        $positions = DB::table('resort_positions')->where('resort_id', $this->resortId)->where('dept_id', $dept)->limit(2)->pluck('id')->all();
        [$a, $b] = $positions;
        $months = fn ($v) => array_fill(0, 12, $v);
        $payload = [
            'resort_id' => $this->resortId, 'dept_id' => $dept, 'year' => now()->year + 1, 'employment_type' => 'Permanent',
            'monthly_data' => [$a => $months(3), $b => array_replace($months(2), [5 => 4])],
            'filled_positions' => [$a => $months(1), $b => $months(9)],   // b: more filled than headcount → capped
            'vacant_positions' => [$a => $months(50), $b => $months(50)],  // nonsense; must be ignored
            'total_headcount' => 999, 'total_filled_headcount' => 999, 'total_vacant_headcount' => 999,
        ];

        foreach (['manning.responses.saveDraft', 'manning.responses.store'] as $route) {
            $this->as('hod.kitchen')->postJson(route($route), $payload)->assertJson(['success' => true]);
            $response = DB::table('manning_responses')->where('resort_id', $this->resortId)->where('dept_id', $dept)->where('year', now()->year + 1)->first();
            $rows = DB::table('position_monthly_data')->where('manning_response_id', $response->id)->get()->groupBy('position_id');

            $this->assertSame([2], $rows[$a]->pluck('vacantcount')->unique()->values()->all(), "{$route}: 3 seats, 1 filled");
            $this->assertSame([0], $rows[$b]->pluck('vacantcount')->unique()->values()->all(), "{$route}: filled capped at headcount");
            $this->assertSame(4, (int) $rows[$b]->firstWhere('month', 6)->filledcount);
            // per position its busiest month, summed: headcount 3 + 4, filled 1 + 4, vacant 2 + 0
            $this->assertSame([7, 5, 2], [(int) $response->total_headcount, (int) $response->total_filled_positions, (int) $response->total_vacant_positions], $route);
        }
    }
}
