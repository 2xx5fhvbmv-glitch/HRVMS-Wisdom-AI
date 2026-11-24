<?php

namespace Tests\Feature;

use App\Helpers\Common;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Shopkeeper notifications store a shopkeeper id in user_id; an employee with the same id must never see them. */
class WebNotificationShopCollisionTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_the_web_bell_ignores_shop_rows_that_share_the_employee_id()
    {
        $this->buildDemoOrganisation();
        $emp = $this->emp('employee');
        DB::table('resort_notifications')->where('resort_id', $this->resortId)->where('user_id', $emp->id)->delete();
        $row = fn ($module, $msg) => DB::table('resort_notifications')->insertGetId(['resort_id' => $this->resortId, 'user_id' => $emp->id,
            'module' => $module, 'type' => 'x', 'message' => $msg, 'status' => 'unread', 'created_at' => now(), 'updated_at' => now()]);
        $row('Leave', 'mine');
        $shop = $row('Staff Shop', 'a shopkeeper\'s');

        $this->assertSame(1, Common::getNotificationCount($this->resortId, $emp->id));
        $this->as('employee')->postJson(route('resort.Mark.Notification'), ['id' => $shop]);
        $this->assertSame('unread', DB::table('resort_notifications')->where('id', $shop)->value('status'));
    }
}
