<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Accommodation › Configuration, Phase B (docs: phase B plan §2). */
class AccommodationConfigTest extends TestCase
{
    use DatabaseTransactions;

    private ResortAdmin $master;

    protected function setUp(): void
    {
        parent::setUp();
        $this->master = ResortAdmin::where('type', 'super')->where('status', 'Active')->firstOrFail();
        $this->actingAs($this->master, 'resort-admin');
    }

    public function test_escalation_day_can_be_saved_again_and_must_be_1_to_30_days()   // §2.4
    {
        $url = route('resort.accommodation.config.EscalationDay');
        $this->postJson($url, ['EscalationDay' => 5])->assertOk();
        $this->postJson($url, ['EscalationDay' => 5])->assertOk();   // same value again: used to fail "already taken"
        $this->assertSame(1, DB::table('escalation_days')->where('resort_id', $this->master->resort_id)->count());
        foreach (['abc', 0, 31] as $bad) {
            $this->postJson($url, ['EscalationDay' => $bad])->assertStatus(422);
        }
    }

    public function test_create_endpoints_return_the_new_id()   // §2.5
    {
        $name = 'T' . substr(uniqid(), -6);
        $b = $this->postJson(route('resort.accommodation.StoreBuilding'), ['BuildingName' => "Bldg $name"])->assertOk()->json('id');
        $this->assertSame("Bldg $name", DB::table('building_models')->where('id', $b)->value('BuildingName'));

        $r = $this->postJson(route('resort.accommodation.StoreFloorandroom'), ['building_id' => $b, 'Floor' => 1, 'Room' => 101])->assertOk()->json('id');
        $this->assertEquals($b, DB::table('bulidng_and_floor_and_rooms')->where('id', $r)->value('building_id'));

        $t = $this->postJson(route('resort.accommodation.AccommodationTypeStore'), ['AccommodationName' => "type $name"])->assertOk()->json('id');
        $this->assertNotNull(DB::table('accommodation_types')->where('id', $t)->where('resort_id', $this->master->resort_id)->first());

        $c = $this->postJson(route('resort.accommodation.StoreInvCategory'), ['CategoryName' => "cat $name"])->assertOk()->json('id');
        $this->assertNotNull(DB::table('inventory_category_models')->where('id', $c)->first());
    }

    public function test_hr_is_told_once_when_a_room_reaches_its_occupancy_level()   // §2.3
    {
        DB::table('employees')->update(['device_token' => null]);   // notifications also push to phones
        $roomId = DB::table('assing_accommodations')->select('available_a_id')->groupBy('available_a_id')->havingRaw('count(*) >= 2')->value('available_a_id')
            ?? $this->markTestSkipped('No room with 2+ beds.');
        $resortId = DB::table('available_accommodation_models')->where('id', $roomId)->value('resort_id');
        $hrIds = \App\Helpers\Common::getResortHrEmployeeIds($resortId);
        empty($hrIds) && $this->markTestSkipped('Resort has no HR to notify.');
        $beds = \App\Models\AssingAccommodation::where('available_a_id', $roomId)->orderBy('id')->get();
        $beds->each->update(['emp_id' => 0]);
        DB::table('available_accommodation_models')->where('id', $roomId)->update(['Occupancytheresold' => 50, 'occupancy_alert_notified_at' => null]);
        $alerts = fn () => DB::table('resort_notifications')->where('page_id', 'accommodation-occupancy-alert')->where('request_id', $roomId)->count();
        $occupants = DB::table('employees')->where('resort_id', $resortId)->where('status', 'Active')->limit($beds->count())->pluck('id');

        $half = (int) ceil($beds->count() / 2);
        $beds->take($half - 1)->each(fn ($b, $i) => $b->update(['emp_id' => $occupants[$i]]));
        $this->assertSame(0, $alerts(), 'below the level: no alert');

        $beds[$half - 1]->update(['emp_id' => $occupants[$half - 1]]);                  // reaches 50%
        $this->assertGreaterThan(0, $first = $alerts());
        $this->assertNotNull(DB::table('available_accommodation_models')->where('id', $roomId)->value('occupancy_alert_notified_at'));

        if ($beds->count() > $half) { $beds[$half]->update(['emp_id' => $occupants[$half]]); }  // still above: not again
        $this->assertSame($first, $alerts());

        $beds->each->update(['emp_id' => 0]);                                               // empties: flag clears
        $this->assertNull(DB::table('available_accommodation_models')->where('id', $roomId)->value('occupancy_alert_notified_at'));
        $beds->take($half)->each(fn ($b, $i) => $b->update(['emp_id' => $occupants[$i]]));  // crosses again: told again
        $this->assertGreaterThan($first, $alerts());
    }
}
