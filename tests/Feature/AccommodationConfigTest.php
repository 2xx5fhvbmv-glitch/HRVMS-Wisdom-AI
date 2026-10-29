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

    public function test_building_names_are_unique_per_resort_ignoring_case()   // §2.10
    {
        $url = route('resort.accommodation.StoreBuilding');
        $name = 'Dup ' . substr(uniqid(), -5);
        $this->postJson($url, ['BuildingName' => $name])->assertOk();
        $this->postJson($url, ['BuildingName' => strtolower($name)])->assertStatus(422);
        $this->postJson($url, ['BuildingName' => ' ' . strtoupper($name) . ' '])->assertStatus(422);
    }

    public function test_room_list_counts_beds_in_one_query()   // §2.7
    {
        DB::enableQueryLog();
        $rows = $this->getJson(route('resort.accommodation.AvailableAccommodationIndex'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data');
        $bedQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'assing_accommodations'))->count();
        $this->assertSame(1, $bedQueries, 'one grouped bed query, not two per room');
        foreach (array_slice($rows, 0, 5) as $r) {
            $this->assertSame(DB::table('assing_accommodations')->where('available_a_id', $r['id'])->where('emp_id', '!=', 0)->count() . '/'
                . DB::table('assing_accommodations')->where('available_a_id', $r['id'])->count(), $r['Beds']);
            $this->assertEquals(DB::table('available_accommodation_models')->where('id', $r['id'])->value('BuildingName'), $r['building_id']);
        }
    }

    public function test_room_setup_reserves_stock_and_refuses_more_than_is_left()   // §2.8
    {
        $rid = $this->master->resort_id;
        $b = $this->postJson(route('resort.accommodation.StoreBuilding'), ['BuildingName' => 'Stock ' . substr(uniqid(), -5)])->json('id');
        $cat = DB::table('inventory_category_models')->insertGetId(['resort_id' => $rid, 'CategoryName' => 'T', 'created_at' => now(), 'updated_at' => now()]);
        $item = DB::table('inventory_modules')->insertGetId(['resort_id' => $rid, 'Inv_Cat_id' => $cat, 'ItemName' => 'Pillow', 'ItemCode' => 'P' . uniqid(),
            'Quantity' => 3, 'Occupied' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $type = $this->postJson(route('resort.accommodation.AccommodationTypeStore'), ['AccommodationName' => 'Std ' . substr(uniqid(), -5)])->json('id');
        $setup = fn ($room) => ['AvailableCount' => 1, 'BuildingName' => [1 => $b], 'Floor' => [1 => 1], 'RoomNo' => [1 => $room],
            'Accommodation_type_id' => [1 => $type], 'Capacity' => [1 => 1],
            'RoomType' => [1 => 1], 'BedNo' => [1 => 1], 'blockFor' => [1 => 'Male'], 'CleaningSchedule' => [1 => 'Daily'], 'Occupancytheresold' => [1 => 90],
            'Inv_Cat_id' => [1 => [$item]], 'InvQty' => [1 => [$item => 2]]];
        $rooms = fn () => DB::table('available_accommodation_models')->where('BuildingName', $b)->count();

        $this->postJson(route('resort.accommodation.AvailableAccommodationStore'), $setup('101'))->assertOk();
        $this->assertEquals(2, DB::table('inventory_modules')->where('id', $item)->value('Occupied'));
        $this->postJson(route('resort.accommodation.AvailableAccommodationStore'), $setup('102'))->assertStatus(422)->assertJsonFragment(['success' => false]);
        $this->assertEquals(2, DB::table('inventory_modules')->where('id', $item)->value('Occupied'), 'nothing reserved by the refused save');
        $this->assertSame(1, $rooms(), 'the refused room was rolled back');

        // §2.2: read and edit a set-up room's items; stock follows
        $roomId = DB::table('available_accommodation_models')->where('BuildingName', $b)->value('id');
        $occ = fn () => (int) DB::table('inventory_modules')->where('id', $item)->value('Occupied');
        $this->getJson(route('resort.accommodation.AvailableAccommodationItems', $roomId))->assertOk()
            ->assertJsonPath('items.0.id', $item)->assertJsonPath('items.0.qty', 2)->assertJsonPath('items.0.available', 1);
        $upd = fn ($extra) => $this->putJson(route('resort.accommodation.AvailableAccommodationUpdate', $roomId), $extra + ['Accommodation_type_id' => $type,
            'Capacity' => 1, 'RoomType' => 1, 'blockFor' => 'Male', 'CleaningSchedule' => 'Daily', 'Occupancytheresold' => 90]);
        $upd(['items' => [$item => 1]])->assertOk();
        $this->assertSame(1, $occ(), 'lowered to 1: one unit back in stock');
        $upd(['items' => [$item => 3]])->assertOk();
        $this->assertSame(3, $occ());
        $upd(['items' => [$item => 4]])->assertStatus(422);
        $this->assertSame(3, $occ(), 'refused: unchanged');
        $this->assertEquals(3, DB::table('available_accommodation_inv_items')->where('Available_Acc_id', $roomId)->value('quantity'));
        $upd([])->assertOk();
        $this->assertSame(3, $occ(), 'no items section: items untouched');
        $this->deleteJson(route('resort.accommodation.AvailableAccommodationDestroy', $roomId))->assertOk();
        $this->assertSame(0, $occ(), 'deleting the room gives its items back');
    }

    public function test_buildings_keep_gender_and_numbering_and_rooms_take_text_numbers()   // §1 rows 1/3, §1b, §2.1
    {
        $store = route('resort.accommodation.StoreBuilding');
        $b = $this->postJson($store, ['BuildingName' => 'Num ' . substr(uniqid(), -5), 'gender' => 'F', 'room_numbering' => 'custom', 'room_number_pattern' => 'F{F}-{N}'])->assertOk()->json('id');
        $this->assertEquals(['F', 'custom', 'F{F}-{N}'], array_values((array) DB::table('building_models')->where('id', $b)->first(['gender', 'room_numbering', 'room_number_pattern'])));
        $this->postJson($store, ['BuildingName' => 'Bad ' . uniqid(), 'room_numbering' => 'custom', 'room_number_pattern' => 'F{F}-'])->assertStatus(422);   // no counter
        $this->postJson($store, ['BuildingName' => 'Bad ' . uniqid(), 'room_numbering' => 'custom'])->assertStatus(422);                                   // pattern missing
        $this->postJson($store, ['BuildingName' => 'Bad ' . uniqid(), 'gender' => 'Q'])->assertStatus(422);

        $room = route('resort.accommodation.StoreFloorandroom');
        foreach (['G01', 'F1-2', 'A 101'] as $no) {
            $id = $this->postJson($room, ['building_id' => $b, 'Floor' => 1, 'Room' => $no])->assertOk()->json('id');
            $this->assertSame($no, DB::table('bulidng_and_floor_and_rooms')->where('id', $id)->value('Room'));
        }
        $this->postJson($room, ['building_id' => $b, 'Floor' => 1, 'Room' => '<b>1</b>'])->assertStatus(422);
        $this->postJson($room, ['building_id' => $b, 'Floor' => 1, 'Room' => str_repeat('9', 16)])->assertStatus(422);

        // Room update: validated now, and only inside the caller's resort
        $rid = DB::table('bulidng_and_floor_and_rooms')->where('building_id', $b)->where('Room', 'G01')->value('id');
        $upd = route('resort.accommodation.FloorAndRoomUpdate');
        $this->putJson($upd, ['Main_id' => base64_encode($rid), 'building_id' => $b, 'Floor' => 1, 'Room' => 'G02'])->assertOk();
        $this->assertSame('G02', DB::table('bulidng_and_floor_and_rooms')->where('id', $rid)->value('Room'));
        $this->putJson($upd, ['Main_id' => base64_encode($rid), 'building_id' => $b, 'Floor' => 1, 'Room' => 'F1-2'])->assertStatus(422);   // taken
        $foreign = DB::table('building_models')->where('resort_id', '!=', $this->master->resort_id)->value('id');
        $this->putJson($upd, ['Main_id' => base64_encode($rid), 'building_id' => $foreign, 'Floor' => 1, 'Room' => 'G03'])->assertStatus(422);
    }

    public function test_import_reads_dashes_in_building_names_and_room_numbers()   // §1b round trip through the Excel template text
    {
        $name = 'Blk-' . substr(uniqid(), -4);
        $b = $this->postJson(route('resort.accommodation.StoreBuilding'), ['BuildingName' => $name])->json('id');
        $type = $this->postJson(route('resort.accommodation.AccommodationTypeStore'), ['AccommodationName' => 'Imp ' . substr(uniqid(), -5)])->json('id');
        (new \App\Imports\ImportAvailableAccommodation)->model([
            'buildingname' => $name, 'floor' => "$name - 1", 'room' => "$name / Floor - 1 / Room - F1-2",
            'typeofaccommodation' => DB::table('accommodation_types')->where('id', $type)->value('AccommodationName'), 'capacity' => 1,
            'roomtype' => array_values(config('settings.eligibilty'))[0], 'bedno' => 1, 'blockfor' => 'Male', 'itemname' => null,
            'cleaningshedule' => 'Daily', 'roomstatus' => 'Available', 'occupancytheresold' => 90,
        ]);
        $this->assertSame(['1', 'F1-2'], array_map('strval', array_values((array) DB::table('available_accommodation_models')->where('BuildingName', $b)->first(['Floor', 'RoomNo']))));
    }

    public function test_bulk_building_and_bulk_room_setup_are_all_or_nothing()   // §2.6
    {
        $rid = $this->master->resort_id;
        $url = route('resort.accommodation.StoreBuildingWithFloors');
        $name = 'Bulk ' . substr(uniqid(), -5);
        $bad = ['BuildingName' => "$name x", 'floors' => [['floor' => 0, 'rooms' => ['G01', 'G02']], ['floor' => 1, 'rooms' => ['101', 'G02']]]];
        $this->postJson($url, $bad)->assertStatus(422);                                                  // G02 twice in the building
        $this->assertSame(0, DB::table('building_models')->where('BuildingName', "$name x")->count(), 'nothing half-built');

        $res = $this->postJson($url, ['BuildingName' => $name, 'gender' => 'M', 'floors' => [['floor' => 0, 'rooms' => ['G01', 'G02']], ['floor' => 1, 'rooms' => ['101']]]])
            ->assertOk()->json();
        $this->assertCount(3, $res['rooms']);
        $this->assertSame(3, DB::table('bulidng_and_floor_and_rooms')->where('building_id', $res['id'])->count());

        $type = $this->postJson(route('resort.accommodation.AccommodationTypeStore'), ['AccommodationName' => 'Bulk ' . substr(uniqid(), -5)])->json('id');
        $cat = DB::table('inventory_category_models')->insertGetId(['resort_id' => $rid, 'CategoryName' => 'T', 'created_at' => now(), 'updated_at' => now()]);
        $item = DB::table('inventory_modules')->insertGetId(['resort_id' => $rid, 'Inv_Cat_id' => $cat, 'ItemName' => 'Lamp', 'ItemCode' => 'L' . uniqid(),
            'Quantity' => 10, 'Occupied' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $setup = ['Accommodation_type_id' => $type, 'Capacity' => 2, 'RoomType' => 6, 'blockFor' => 'Male', 'CleaningSchedule' => 'Daily',
            'Occupancytheresold' => 90, 'items' => [$item => 2]];
        $rooms = [['building_id' => $res['id'], 'floor' => 0, 'room' => 'G01'], ['building_id' => $res['id'], 'floor' => 0, 'room' => 'G02']];
        $batch = route('resort.accommodation.SetupRoomsBatch');

        $this->postJson($batch, ['rooms' => array_merge($rooms, [['building_id' => $res['id'], 'floor' => 0, 'room' => 'G99']]), 'setup' => $setup])
            ->assertStatus(422)->assertJsonPath('failed.0.index', 2);                                      // one bad room: nothing saved
        $this->assertSame(0, DB::table('available_accommodation_models')->where('BuildingName', $res['id'])->count());
        $this->assertEquals(0, DB::table('inventory_modules')->where('id', $item)->value('Occupied'));

        $this->postJson($batch, ['rooms' => $rooms, 'setup' => $setup])->assertOk()->assertJsonCount(2, 'rooms');
        $this->assertSame(4, DB::table('assing_accommodations')->join('available_accommodation_models as a', 'a.id', '=', 'assing_accommodations.available_a_id')
            ->where('a.BuildingName', $res['id'])->count(), '2 rooms x 2 beds');
        $this->assertEquals(4, DB::table('inventory_modules')->where('id', $item)->value('Occupied'), '2 rooms x 2 lamps');

        // Same batch again as an edit: 3 beds, 1 lamp each; a bed can't be cut below the staff assigned
        $this->postJson($batch, ['rooms' => $rooms, 'setup' => ['Capacity' => 3, 'items' => [$item => 1]] + $setup])->assertOk();
        $this->assertEquals(2, DB::table('inventory_modules')->where('id', $item)->value('Occupied'));
        $g01 = DB::table('available_accommodation_models')->where('BuildingName', $res['id'])->where('RoomNo', 'G01')->value('id');
        \App\Models\AssingAccommodation::where('available_a_id', $g01)->take(2)->get()->each->update(['emp_id' => DB::table('employees')->where('resort_id', $rid)->value('id')]);
        $this->postJson($batch, ['rooms' => [$rooms[0]], 'setup' => ['Capacity' => 1] + $setup])->assertStatus(422)
            ->assertJsonPath('failed.0.message', 'Room G01 has 2 staff assigned, so it needs at least 2 beds.');
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
