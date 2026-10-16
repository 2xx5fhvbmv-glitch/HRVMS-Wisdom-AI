<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Founder 2026-10-15: an ignored Yes/Keep prompt is re-sent every 3 days, the same as Keep. */
class AccommodationReleaseRepromptTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_unanswered_prompt_is_re_sent_every_three_days()
    {
        $this->buildDemoOrganisation();
        DB::table('employees')->update(['device_token' => null]); // push is raw curl — no device gets one from a test
        DB::table('accommodation_release_decisions')->update(['decision' => 'closed']); // only this test's decisions are open

        $ignored = $this->emp('employee');
        $fresh = DB::table('employees')->where('resort_id', $this->resortId)->where('rank', 6)->where('id', '!=', $ignored->id)->first();
        foreach ([$ignored->id, $fresh->id] as $id) {
            DB::table('employees')->where('id', $id)->update(['status' => 'Inactive']);
            DB::table('assing_accommodations')->insert(['resort_id' => $this->resortId, 'available_a_id' => DB::table('available_accommodation_models')->value('id'), 'emp_id' => $id, 'effected_date' => now()->subYear()->toDateString()]);
            DB::table('final_settlements')->insert(['employee_id' => $id, 'status' => 'finalized', 'created_at' => now(), 'updated_at' => now()]);
        }
        // Prompted 4 days ago (before next_check_on was set on new prompts), never answered.
        $old = DB::table('accommodation_release_decisions')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $ignored->id, 'decision' => 'pending',
            'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)]);
        $prompts = fn () => DB::table('resort_notifications')->where('resort_id', $this->resortId)->where('type', 'Vacate Accommodation?')
            ->where('request_id', $old)->count();

        $this->artisan('accommodation:release-follow-up')->assertSuccessful();
        $hrCount = count(\App\Helpers\Common::getResortHrEmployeeIds($this->resortId));
        $this->assertSame($hrCount, $prompts(), 're-sent to HR');
        $this->assertSame(now()->addDays(3)->toDateString(), substr((string) DB::table('accommodation_release_decisions')->where('id', $old)->value('next_check_on'), 0, 10));
        $new = DB::table('accommodation_release_decisions')->where('employee_id', $fresh->id)->first();
        $this->assertSame(['pending', now()->addDays(3)->toDateString()], [$new->decision, substr((string) $new->next_check_on, 0, 10)], 'a new prompt is due again in 3 days');

        $this->artisan('accommodation:release-follow-up')->assertSuccessful();
        $this->assertSame($hrCount, $prompts(), 'not again the same day');
    }
}
