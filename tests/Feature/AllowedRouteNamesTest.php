<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/**
 * WAI Guide (2026-11-06): Common::allowedRouteNames() answers "which of these screens may this person view" from the
 * real Page Permission ticks, for many routes in at most 2 queries and without UNMAPPED_ROUTE logging.
 */
class AllowedRouteNamesTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    const BUDGET = ['resort.budget.manning', 'resort.budget.viewbudget', 'resort.budget.consolidatebudget', 'resort.budget.config'];

    private int $view;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->view = (int) config('settings.resort_permissions.view');
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /** Start a position with no ticks at all (the demo gives every login the full menu). */
    private function untick(string $login): void
    {
        $emp = $this->emp($login);
        DB::table('resort_interal_pages_permissions')->where('resort_id', $this->resortId)->where('position_id', $emp->Position_id)->delete();
        Common::forgetAllowedRoutes($this->resortId, $emp->Position_id);
    }

    /** Pages this resort has switched on (resort_pagewise_permissions) — what a position can be ticked for. */
    private function resortRoutes(): array
    {
        return DB::table('module_pages as mp')->join('resort_pagewise_permissions as rp', fn ($j) => $j->on('rp.page_permission_id', '=', 'mp.id')->on('rp.Module_id', '=', 'mp.Module_Id'))
            ->where('rp.resort_id', $this->resortId)->where('mp.type', 'normal')->whereIn('mp.TypeOfPage', ['InsideOfMenu', 'InsideOfPage'])
            ->whereNotNull('mp.internal_route')->distinct()->pluck('mp.internal_route')->all();
    }

    /** Give a position the view tick on pages, the way the Page Permission screen stores it. */
    private function tick(string $login, array $routes): void
    {
        $emp = $this->emp($login);
        foreach (DB::table('module_pages')->whereIn('internal_route', $routes)->where('type', 'normal')->pluck('id') as $pageId) {
            DB::table('resort_interal_pages_permissions')->insert(['resort_id' => $this->resortId, 'Dept_id' => $emp->Dept_id,
                'position_id' => $emp->Position_id, 'page_id' => $pageId, 'Permission_id' => $this->view, 'created_at' => now(), 'updated_at' => now()]);
        }
        Common::forgetAllowedRoutes($this->resortId, $emp->Position_id);
    }

    private function allowedFor(string $login, array $routes): array
    {
        $this->as($login);
        return Common::allowedRouteNames($routes);
    }

    public function test_a_hod_without_the_budget_tick_is_not_offered_budget_screens()
    {
        $this->untick('hod.kitchen');
        $this->assertSame([], $this->allowedFor('hod.kitchen', self::BUDGET));
        $this->tick('hod.kitchen', ['resort.budget.viewbudget']);
        $this->assertSame(['resort.budget.viewbudget'], $this->allowedFor('hod.kitchen', self::BUDGET));
    }

    public function test_hr_with_every_tick_is_offered_every_screen_it_has()
    {
        $mine = $this->resortRoutes();
        $this->assertGreaterThan(50, count($mine));
        $this->untick('hr');
        $this->tick('hr', $mine);
        $this->assertEqualsCanonicalizing($mine, $this->allowedFor('hr', $mine));
    }

    public function test_saving_page_permissions_shows_at_once()
    {
        $hod = $this->emp('hod.kitchen');
        $this->untick('hod.kitchen');
        $this->tick('hod.kitchen', ['resort.budget.viewbudget']);
        $this->assertSame(['resort.budget.viewbudget'], $this->allowedFor('hod.kitchen', self::BUDGET));   // now cached

        // HR saves the position's ticks without Budget (the real endpoint) — the cache is cleared on save.
        $other = DB::table('module_pages')->where('internal_route', 'resort.workforceplan.dashboard')->value('id')
            ?? DB::table('module_pages')->where('type', 'normal')->where('Module_Id', '!=', 1)->value('id');
        $this->actingAs(ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->first(), 'resort-admin');
        $this->postJson(route('resorts.update_internal_permissions'), ['department' => $hod->Dept_id, 'position' => $hod->Position_id,
            'Resort_page_permissions' => [$other => [$this->view]]])->assertJson(['success' => true]);

        $this->assertSame([], $this->allowedFor('hod.kitchen', self::BUDGET));
    }

    public function test_at_most_two_queries_and_no_unmapped_route_logging()
    {
        $this->untick('hod.kitchen');
        $this->as('hod.kitchen');
        auth('resort-admin')->user()->GetEmployee;   // the page has already loaded this
        $routes = array_merge(self::BUDGET, ['wai.guide.not.a.page', 'another.unmapped.route']);
        Log::spy();

        DB::enableQueryLog();
        $first = Common::allowedRouteNames($routes);
        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()), 'cache miss');
        DB::flushQueryLog();
        $this->assertSame($first, Common::allowedRouteNames($routes));
        $this->assertCount(0, DB::getQueryLog(), 'cache hit');

        $this->assertSame(['wai.guide.not.a.page', 'another.unmapped.route'], $first, 'unmapped routes keep today\'s default (allowed)');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_super_admin_and_the_bypass_routes_behave_as_before()
    {
        $this->actingAs(ResortAdmin::where('resort_id', $this->resortId)->where('type', 'super')->first(), 'resort-admin');
        $this->assertSame(self::BUDGET, Common::allowedRouteNames(self::BUDGET));

        $this->assertSame(['resort.Page.Permission', 'resort.getMenuData'], $this->allowedFor('hod.kitchen', ['resort.Page.Permission', 'resort.getMenuData']));
        auth('resort-admin')->logout();
        $this->assertSame([], Common::allowedRouteNames(self::BUDGET), 'nobody signed in');
    }

    public function test_the_wai_guide_gets_the_ticked_menu_screens_and_sees_a_change_on_the_next_page()
    {
        $guideData = function () {
            $html = $this->get(route('resort.master.hod_dashboard'))->assertOk()->getContent();
            preg_match("/data-menu='([^']*)' data-allowed='([^']*)'/", $html, $m);
            $this->assertNotEmpty($m, 'a non-HR user gets data-menu / data-allowed on the guide notch');
            return [json_decode(html_entity_decode($m[1]), true), json_decode(html_entity_decode($m[2]), true)];
        };
        $this->untick('hod.kitchen');
        $this->as('hod.kitchen');
        [$menu, $allowed] = $guideData();
        $this->assertSame([], $allowed, 'no ticks, no menu screens');
        $budget = array_values(array_intersect(self::BUDGET, $menu));
        $this->assertNotEmpty($budget, 'the guide has budget screens');

        $this->tick('hod.kitchen', [$budget[0]]);
        $this->as('hod.kitchen');
        $this->assertSame([$budget[0]], $guideData()[1]);
    }

    public function test_it_agrees_with_check_route_wise_permission_on_every_page()
    {
        // Every page in the system, half of this resort's ticked: the batch answer must equal the one-by-one answer.
        $every = DB::table('module_pages')->where('type', 'normal')->whereNotNull('internal_route')->distinct()->pluck('internal_route')->all();
        $this->untick('hod.kitchen');
        $mine = $this->resortRoutes();
        $this->tick('hod.kitchen', array_values(array_filter($mine, fn ($r, $i) => $i % 2 === 0, ARRAY_FILTER_USE_BOTH)));
        $this->as('hod.kitchen');
        $allowed = array_flip(Common::allowedRouteNames($every));
        $differ = array_values(array_filter($every, fn ($r) => (bool) Common::checkRouteWisePermission($r, $this->view) !== isset($allowed[$r])));
        $this->assertSame([], $differ);
    }
}
