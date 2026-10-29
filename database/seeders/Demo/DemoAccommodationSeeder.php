<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo ENV: Accommodation. Staff housing as ConfigrationController / AssignAccommodationController build
 * it — one available_accommodation_models row per room (BuildingName holds the building id, RoomType the
 * rank the room is for, blockFor the gender), one assing_accommodations row per bed (emp_id 0 = free) —
 * plus inventory per room, a maintenance history at every stage of the request flow (child rows use the
 * app's own spellings 'Assinged' / 'Resolvedawaiting'), housekeeping requests and schedules, and room moves.
 *
 * Casual staff and some Maldivian line staff live on their home island and hold no bed, so the Assign
 * screen has people to house. Open requests older than the 1-day escalation threshold are already marked
 * as escalated, so the 09:30 reminder doesn't fire on a fresh demo.
 */
class DemoAccommodationSeeder
{
    use SeedsDemoRows;

    const OPENED = '2024-01-15'; // when the staff blocks opened
    const ROOMS_PER_FLOOR = 8;
    const ITEMS = [ // [category, item, per, qty per unit, executive only]
        ['Furniture', 'Single Bed', 'per_person', 1, false], ['Furniture', 'Wardrobe', 'per_person', 1, false],
        ['Linen & Bedding', 'Mattress', 'per_person', 1, false], ['Linen & Bedding', 'Bed Linen Set', 'per_person', 2, false],
        ['Bathroom', 'Towel Set', 'per_person', 2, false], ['Appliances', 'Air Conditioner', 'per_room', 1, false],
        ['Appliances', 'Ceiling Fan', 'per_room', 1, false], ['Appliances', 'Water Heater', 'per_room', 1, false],
        ['Furniture', 'Study Desk', 'per_room', 1, false], ['Electronics', 'LED TV', 'per_room', 1, true],
        ['Electronics', 'Mini Fridge', 'per_room', 1, true], ['Electronics', 'Kettle', 'per_room', 1, true],
    ];
    const ISSUES = [ // [item, description, priority]
        ['Air Conditioner', 'AC not cooling, water dripping from the indoor unit', 'High'], ['Air Conditioner', 'AC making a loud noise at night', 'Medium'],
        ['Water Heater', 'No hot water in the shower', 'High'], ['Ceiling Fan', 'Fan wobbling and making noise', 'Low'],
        ['Wardrobe', 'Wardrobe door hinge broken', 'Low'], ['Single Bed', 'Bed frame slat cracked', 'Medium'],
        [null, 'Bathroom tap leaking', 'Medium'], [null, 'Window lock not closing', 'Low'], [null, 'Light in the corridor not working', 'Medium'],
        ['Mini Fridge', 'Fridge not cooling', 'Medium'], [null, 'Blocked drain in the bathroom', 'High'], ['Study Desk', 'Desk drawer stuck', 'Low'],
    ];
    // Request flow, in order; each closed request went through all of it.
    const FLOW = ['pending', 'Open', 'Assigned', 'In-Progress', 'ResolvedAwaiting', 'Closed'];
    // [status, days ago] for the 25 requests: 16 closed, then one of each other state.
    const REQUESTS = [['Closed', 88], ['Closed', 81], ['Closed', 76], ['Closed', 70], ['Closed', 64], ['Closed', 59], ['Closed', 52], ['Closed', 47],
        ['Closed', 41], ['Closed', 36], ['Closed', 30], ['Closed', 25], ['Closed', 19], ['Closed', 15], ['Closed', 11], ['Closed', 8],
        ['Rejected', 20], ['On-Hold', 12], ['pending', 0], ['pending', 1], ['Open', 4], ['Open', 3], ['Assigned', 3], ['In-Progress', 2], ['ResolvedAwaiting', 5]];

    private array $counts = ['beds' => 0, 'housed staff' => 0, 'maintenance requests' => 0];
    private array $bedsByEmployee = []; // employees.id => [room row, bed id]

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $now = Carbon::now();
        $hr = $this->login('hr');
        $stamp = ['resort_id' => $this->resortId, 'created_by' => $hr->admin, 'modified_by' => $hr->admin,
            'created_at' => Carbon::parse(self::OPENED), 'updated_at' => Carbon::parse(self::OPENED)];

        $types = [];
        foreach (['Four Share' => ['#2E86DE', 4], 'Double Share' => ['#10AC84', 2], 'Single Share' => ['#F79F1F', 1]] as $name => [$colour, $capacity]) {
            $types[$capacity] = [$this->row('accommodation_types', $stamp + ['AccommodationName' => $name, 'Color' => $colour]), $colour];
        }
        $buildings = [];
        foreach (['Staff Block A', 'Staff Block B', 'Executive Residence'] as $name) {
            $buildings[$name] = $this->row('building_models', $stamp + ['BuildingName' => $name]);
        }
        $items = $this->inventory($stamp);

        // Who gets a bed: everyone except casual staff and a few Maldivian line staff who live locally.
        $logins = fn ($p) => !str_starts_with((string) $p->email, strtolower(config('demo.prefix')) . '-');
        $locals = $this->people()->filter(fn ($p) => (int) $p->rank === 6 && $p->nationality === 'Maldivian' && !$logins($p))->shuffle()->take(9)->keys();
        // Leavers still in their bed: someone serving notice, and a settled leaver waiting on HR's release decision.
        $leavers = $this->staff(['Offboarding', 'Inactive'])->filter(fn ($p) => $p->employment_type !== 'Casual'
            && DB::table('employee_resignation')->where('employee_id', $p->id)->where('status', 'Approved')->exists());
        $housed = $this->people()->reject(fn ($p) => $p->employment_type === 'Casual' || $locals->contains($p->id))->union($leavers);
        $gender = fn ($p) => ucfirst($p->gender ?: 'male') === 'Female' ? 'Female' : 'Male';

        // Rooms per building/floor: line + supervisor in four-share blocks by gender, managers in doubles,
        // HOD / EXCOM in singles, the GM's single on its own floor. One spare room per four-share group,
        // plus a free single each for a male and a female new hire.
        $groups = [];
        foreach ($housed as $p) {
            $rank = (int) $p->rank;
            [$building, $floor, $capacity, $roomType] = match (true) {
                $rank === 8 => ['Executive Residence', 3, 1, 8],
                in_array($rank, [1, 2], true) => ['Executive Residence', 2, 1, $rank],
                $rank === 4 => ['Executive Residence', 1, 2, 4],
                default => [$gender($p) === 'Male' ? 'Staff Block A' : 'Staff Block B', null, 4, $rank === 5 ? 5 : 6], // interns live with line staff
            };
            $groups["{$building}|{$floor}|{$capacity}|{$roomType}|" . $gender($p)][] = $p;
        }
        $groups['Executive Residence|2|1|2|Male'] ??= [];
        $groups['Executive Residence|2|1|2|Male'][] = null; // free single for a new HOD
        $groups['Executive Residence|2|1|1|Female'] ??= [];
        $groups['Executive Residence|2|1|1|Female'][] = null;
        ksort($groups);
        $nextRoom = [];
        $maintenanceRoom = null;
        foreach ($groups as $key => $people) {
            [$building, $floor, $capacity, $roomType, $sex] = explode('|', $key);
            $people = array_values($people);
            $rooms = (int) ceil(count($people) / $capacity) + ($capacity == 4 ? 1 : 0);
            for ($r = 0; $r < $rooms; $r++) {
                $f = $floor !== '' ? (int) $floor : 1 + intdiv($nextRoom[$building] ?? 0, self::ROOMS_PER_FLOOR);
                $nextRoom[$building] = ($nextRoom[$building] ?? 0) + 1;
                $roomNo = $f * 100 + ($nextRoom["{$building}|{$f}"] = ($nextRoom["{$building}|{$f}"] ?? 0) + 1);
                $occupants = array_slice($people, $r * $capacity, $capacity);
                $spare = $capacity == 4 && $r === $rooms - 1 && $maintenanceRoom === null && $building === 'Staff Block A';
                $room = $this->room($buildings[$building], $f, $roomNo, $types[$capacity], (int) $roomType, $sex, (int) $capacity, $occupants,
                    $spare ? 'Under Maintenance' : 'Available', $items, $building === 'Executive Residence', $stamp);
                $maintenanceRoom ??= $spare ? $room : null;
            }
        }

        // What accommodation:release-follow-up does for an Inactive bed holder with a finalized settlement: ask HR, Yes or Keep.
        foreach ($leavers->where('status', 'Inactive') as $p) {
            $asked = $now->copy()->subDays(2);
            $this->row('accommodation_release_decisions', ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'decision' => 'pending',
                'next_check_on' => $asked->copy()->addDays(3)->format('Y-m-d'), 'created_at' => $asked, 'updated_at' => $asked]);
        }

        $this->moves($hr);
        $this->maintenance($items, $maintenanceRoom, $hr, $now);
        $this->housekeeping($hr, $now);

        Cache::forget("accommodation_insights:{$this->resortId}");
        return $this->counts;
    }

    /** Inventory items: returns name => [id, per, qty, executive only]; quantities are set once rooms exist. */
    private function inventory(array $stamp): array
    {
        $categories = [];
        $items = [];
        foreach (self::ITEMS as $i => [$category, $item, $per, $qty, $exec]) {
            $categories[$category] ??= $this->row('inventory_category_models', $stamp + ['CategoryName' => $category]);
            $items[$item] = [$this->row('inventory_modules', $stamp + ['Inv_Cat_id' => $categories[$category], 'ItemName' => $item,
                'ItemCode' => 'INV-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'PurchageDate' => self::OPENED, 'Occupied' => 0,
                'Quantity' => 0, 'MinMumStockQty' => 5, 'assignment_type' => $per, 'default_qty_per_unit' => $qty]), $per, $qty, $exec, 0];
        }
        return $items;
    }

    private function room(int $buildingId, int $floor, int $roomNo, array $type, int $roomType, string $sex, int $capacity, array $occupants,
        string $status, array &$items, bool $executive, array $stamp): object
    {
        $this->row('bulidng_and_floor_and_rooms', ['resort_id' => $this->resortId, 'building_id' => $buildingId, 'Floor' => $floor, 'Room' => $roomNo,
            'created_at' => $stamp['created_at'], 'updated_at' => $stamp['created_at']]);
        $roomId = $this->row('available_accommodation_models', $stamp + ['BuildingName' => (string) $buildingId, 'Floor' => $floor,
            'RoomNo' => (string) $roomNo, 'Accommodation_type_id' => $type[0], 'RoomType' => $roomType, 'BedNo' => $capacity,
            'blockFor' => $sex, 'Capacity' => $capacity, 'CleaningSchedule' => $executive ? ($roomType === 4 ? 'By Weekly' : 'Daily') : 'Weekly',
            'RoomStatus' => $status, 'Occupancytheresold' => 90, 'Colour' => $type[1]]);
        foreach ($items as $name => &$item) {
            [$itemId, $per, $qty, $execOnly] = $item;
            if ($execOnly && !$executive) {
                continue;
            }
            $quantity = $per === 'per_person' ? $qty * $capacity : $qty;
            $this->row('available_accommodation_inv_items', ['Available_Acc_id' => $roomId, 'Item_id' => $itemId, 'quantity' => $quantity,
                'created_at' => $stamp['created_at'], 'updated_at' => $stamp['created_at']]);
            $item[4] += $quantity; // occupied = sum of what the rooms hold
        }
        unset($item);
        for ($b = 1; $b <= $capacity; $b++) {
            $p = $occupants[$b - 1] ?? null;
            $since = $p ? Carbon::parse(max(self::OPENED, (string) $p->joining_date)) : Carbon::parse(self::OPENED);
            $bedId = $this->row('assing_accommodations', ['resort_id' => $this->resortId, 'available_a_id' => $roomId, 'emp_id' => $p->id ?? 0,
                'effected_date' => $since->format('Y-m-d'), 'BedNo' => "Bed No {$b}", 'created_at' => $since, 'updated_at' => $since]);
            if ($p) {
                if (isset($this->people()[$p->id])) { // leavers raise no requests and are not moved
                    $this->bedsByEmployee[$p->id] = [(object) ['id' => $roomId, 'building' => $buildingId, 'floor' => $floor, 'room' => $roomNo], $bedId];
                }
                $this->row('transfer_accommodations', ['resort_id' => $this->resortId, 'Emp_id' => $p->id, 'OldAccommodation_id' => null,
                    'NewAccommodation_id' => $bedId, 'NewdDate' => $since->format('Y-m-d'), 'Reason' => 'Initial Assignment',
                    'created_by' => $this->adminId, 'created_at' => $since, 'updated_at' => $since]);
                $this->counts['housed staff']++;
            }
            $this->counts['beds']++;
        }
        // Stock = what is placed in rooms plus a margin; towel sets run low so the low-stock card has something.
        foreach ($items as $name => [$itemId, , , , $occupied]) {
            DB::table('inventory_modules')->where('id', $itemId)->update(['Occupied' => $occupied,
                'Quantity' => $name === 'Towel Set' ? $occupied + 2 : (int) ceil($occupied * 1.12)]);
        }
        return (object) ['id' => $roomId, 'building' => $buildingId, 'floor' => $floor, 'room' => $roomNo];
    }

    /** A few people moved rooms (noise, roommate requests): history rows, as MoveToNext writes them. */
    private function moves(object $hr): void
    {
        foreach (collect($this->bedsByEmployee)->keys()->shuffle()->take(4) as $empId) {
            [$room, $bedId] = $this->bedsByEmployee[$empId];
            $moved = Carbon::today()->subDays(mt_rand(20, 200));
            $this->row('transfer_accommodations', ['resort_id' => $this->resortId, 'Emp_id' => $empId, 'OldAccommodation_id' => $bedId,
                'NewAccommodation_id' => $bedId, 'OldDate' => $moved->copy()->subMonths(3)->format('Y-m-d'), 'NewdDate' => $moved->format('Y-m-d'),
                'Reason' => $this->pick(['AC noise in the old room', 'Roommate request', 'Moved closer to the department', 'Room under repair']),
                'created_by' => $hr->admin, 'created_at' => $moved, 'updated_at' => $moved]);
        }
    }

    private function maintenance(array $items, ?object $heldRoom, object $hr, Carbon $now): void
    {
        $chief = $this->login('hod.engineering');
        $technicians = $this->people()->where('Dept_id', $chief->Dept_id)->where('rank', 6)->values();
        $housed = collect($this->bedsByEmployee);
        $employee = $this->login('employee');
        foreach (self::REQUESTS as $n => [$status, $daysAgo]) {
            $raisedBy = $n === 18 && isset($this->bedsByEmployee[$employee->id]) ? $employee->id : $housed->keys()->random();
            [$room] = $this->bedsByEmployee[$raisedBy];
            if ($status === 'On-Hold' && $heldRoom) {
                $room = $heldRoom;
            }
            // Block A's AC is the hotspot.
            [$itemName, $issue, $priority] = $n % 3 === 0 ? self::ISSUES[0] : self::ISSUES[$n % count(self::ISSUES)];
            $raised = Carbon::today()->subDays($daysAgo)->setTime(mt_rand(7, 21), mt_rand(0, 59));
            $tech = $technicians->isNotEmpty() ? $technicians[$n % $technicians->count()] : $chief;
            $stage = array_search($status, self::FLOW, true);
            $stage = $stage === false ? ($status === 'Rejected' ? 0 : 1) : $stage; // rejected at HR; held after forwarding
            $forwarded = $raised->copy()->addHours(mt_rand(3, 30))->min($now);
            $closedAt = $raised->copy()->addDays(mt_rand(1, 6))->min($now);
            $requestId = $this->row('maintanace_requests', [
                'resort_id' => $this->resortId, 'building_id' => $room->building, 'item_id' => $itemName ? $items[$itemName][0] : null,
                'FloorNo' => $room->floor, 'RoomNo' => (string) $room->room, 'Request_id' => 'REQ-' . strtoupper(Str::random(13)),
                'Raised_By' => $raisedBy, 'Assigned_To' => match (true) { $stage >= 2 => $tech->id, $stage === 1 => $chief->id, default => null },
                'descriptionIssues' => $issue, 'priority' => $priority, 'Status' => $status,
                'date' => ($stage >= 1 ? $forwarded : $raised)->format('Y-m-d'), 'start_time' => $raised->format('H:i'),
                'ReasonOnHold' => $status === 'On-Hold' ? 'Awaiting compressor from Malé supplier' : null,
                'hold_until' => $status === 'On-Hold' ? Carbon::today()->addDays(7)->format('Y-m-d') : null,
                'RejactionReason' => $status === 'Rejected' ? 'Duplicate of an earlier request for the same room.' : null,
                // Already reminded once, so the 09:30 escalation job doesn't fire on a fresh demo.
                'escalation_notified_at' => $status !== 'Closed' && $daysAgo > 1 ? $raised->copy()->addDays(2)->setTime(9, 30)->min($now) : null,
                'created_at' => $raised, 'updated_at' => $status === 'Closed' ? $closedAt : $forwarded,
            ]);
            $this->children($requestId, $stage, $status, $raisedBy, $hr, $chief, $tech, $raised, $forwarded, $closedAt);
            $this->counts['maintenance requests']++;
        }
    }

    /** Approval trail as the web and mobile flow writes it (ApprovedBy 0 = the next step nobody has taken). */
    private function children(int $requestId, int $stage, string $status, int $raisedBy, object $hr, object $chief, object $tech,
        Carbon $raised, Carbon $forwarded, Carbon $closedAt): void
    {
        $steps = [[(string) $hr->id, 'Open', 3], [(string) $chief->id, 'Assinged', 2], [(string) $tech->id, 'In-Progress', 6],
            [(string) $tech->id, 'Resolvedawaiting', 6], [(string) $raisedBy, 'Closed', 6]];
        $done = $status === 'Closed' ? 5 : min($stage, 4);
        $at = fn ($i) => $raised->copy()->addHours(4 + $i * 10)->min($status === 'Closed' ? $closedAt : $forwarded->copy()->addHours($i * 6)->min(now()));
        $childIds = [];
        foreach (array_slice($steps, 0, $done) as $i => [$by, $childStatus, $rank]) {
            $childIds[$i] = $this->row('child_maintanance_requests', ['resort_id' => $this->resortId, 'maintanance_request_id' => $requestId,
                'ApprovedBy' => $by, 'rank' => $rank, 'Status' => $childStatus, 'date' => $at($i)->format('Y-m-d'),
                'comments' => $childStatus === 'Resolvedawaiting' ? 'Fixed and tested with the occupant.' : null,
                'created_at' => $at($i), 'updated_at' => $at($i)]);
        }
        if (!in_array($status, ['Closed', 'Rejected', 'On-Hold'], true)) {
            $this->row('child_maintanance_requests', ['resort_id' => $this->resortId, 'maintanance_request_id' => $requestId, 'ApprovedBy' => '0',
                'Status' => 'pending', 'date' => $forwarded->format('Y-m-d'), 'created_at' => $forwarded, 'updated_at' => $forwarded]);
        }
        // Sign-off rows once the technician marked it resolved: HOD approved, then HR sent it to the occupant.
        if (isset($childIds[3])) {
            foreach ([[$chief, 2, 'Approved'], [$hr, 3, $status === 'Closed' ? 'Approved' : 'Assinged']] as [$who, $rank, $s]) {
                $this->row('child_approved_maintanace_requests', ['resort_id' => $this->resortId, 'child_maintanance_request_id' => $childIds[3],
                    'maintanance_request_id' => $requestId, 'ApprovedBy' => (string) $who->id, 'rank' => $rank, 'Status' => $s,
                    'date' => $at(3)->format('Y-m-d'), 'created_at' => $at(3), 'updated_at' => $at(3)]);
            }
        }
    }

    /** Housekeeping service catalog, requests from senior staff, and today's room-cleaning schedule. */
    private function housekeeping(object $hr, Carbon $now): void
    {
        $services = [];
        foreach (['Housekeeping - Once a Week', 'Housekeeping - Twice a Week', 'Housekeeping - 3 Times a Week', 'Linen Change', 'Laundry Pickup', 'Deep Cleaning'] as $name) {
            $services[$name] = $this->row('housekeeping_service_catalog', ['resort_id' => $this->resortId, 'name' => $name, 'status' => 'active',
                'created_at' => $now->copy()->subMonths(6), 'updated_at' => $now->copy()->subMonths(6)]);
        }
        $hk = $this->login('hod.housekeeping');
        $attendants = $this->people()->where('Dept_id', $hk->Dept_id)->whereIn('rank', [5, 6])->values();
        $seniors = collect($this->bedsByEmployee)->keys()->filter(fn ($id) => in_array((int) $this->people()[$id]->rank, [1, 2, 4], true))->values();
        $batch = (string) Str::uuid();
        foreach ($seniors->take(10) as $i => $empId) {
            [$room] = $this->bedsByEmployee[$empId];
            $day = Carbon::today()->addDays($i - 5);
            $status = $day->lt(Carbon::today()) ? ($i === 1 ? 'Not Completed' : 'Completed') : ($day->isToday() ? 'In-Progress' : ($i % 2 ? 'Accepted' : 'Pending'));
            $this->row('housekeeping_requests', ['resort_id' => $this->resortId, 'request_id' => 'HK-' . strtoupper(Str::random(13)),
                'batch_id' => $batch, 'employee_id' => $empId, 'assigned_to_employee_id' => $attendants->isNotEmpty() ? $attendants[$i % $attendants->count()]->id : null,
                'housekeeping_service_id' => $services[$this->pick(['Housekeeping - 3 Times a Week', 'Linen Change', 'Laundry Pickup'])],
                'raised_by' => $hr->id, 'BuildingName' => (string) $room->building, 'FloorNo' => $room->floor, 'RoomNo' => (string) $room->room,
                'remarks' => 'Please clean after 10 am.', 'scheduled_date' => $day->format('Y-m-d'), 'scheduled_time' => '10:30:00',
                'frequency' => 'once', 'status' => $status, 'completed_at' => $status === 'Completed' ? $day->copy()->setTime(11, 15) : null,
                'created_at' => $day->copy()->subDays(2), 'updated_at' => $day]);
        }
        foreach (collect($this->bedsByEmployee)->shuffle()->take(9)->values() as $i => [$room]) {
            $day = $i < 3 ? Carbon::yesterday() : Carbon::today();
            $status = $i < 3 ? 'Complete' : ['Pending', 'Pending', 'Open', 'Open', 'Assigned', 'Assigned'][$i - 3];
            $this->row('housekeeping_schedules', ['resort_id' => $this->resortId, 'available_a_id' => $room->id, 'BuildingName' => (string) $room->building,
                'Floor' => $room->floor, 'RoomNo' => (string) $room->room, 'clean_type' => 'Standard', 'status' => $status,
                'Assigned_To' => in_array($status, ['Assigned', 'Complete'], true) && $attendants->isNotEmpty() ? $attendants[$i % $attendants->count()]->id
                    : ($status === 'Open' ? $hk->id : null),
                'date' => $day->format('Y-m-d'), 'time' => '09:00', 'special_instructions' => 'Change linen and restock towels.',
                'created_by' => $hr->id, 'created_at' => $day->copy()->subDay(), 'updated_at' => $day]);
        }
    }
}
