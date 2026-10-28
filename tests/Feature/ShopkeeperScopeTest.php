<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shopkeeper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Security audit wave 1, shopkeeper portal: W1-06/19/20/36/37. */
class ShopkeeperScopeTest extends TestCase
{
    use DatabaseTransactions;

    private Shopkeeper $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shopkeeper::query()->firstOr(fn () => $this->markTestSkipped('No shopkeeper in this database.'));
        DB::table('shopkeepers')->where('id', $this->shop->id)->update(['must_change_password' => 0]);
        DB::table('payroll_config')->where('resort_id', $this->shop->resort_id)->update(['cutoff_day' => 28]);
        $this->travelTo(now()->startOfMonth()->addHours(10)); // before the payroll cutoff
        $this->actingAs($this->shop->fresh(), 'shopkeeper');
    }

    private function payment(string $status): Payment
    {
        return Payment::create(['shopkeeper_id' => $this->shop->id, 'order_id' => uniqid('T-'), 'purchased_date' => now(), 'quantity' => 1, 'price' => 100, 'status' => $status,
            'emp_id' => Employee::where('resort_id', $this->shop->resort_id)->value('id'), 'product_id' => Product::where('shopkeeper_id', $this->shop->id)->value('id')]);
    }

    public function test_deduct_needs_consent_and_stays_within_the_balance()
    {
        $pending = $this->payment('Pending Consent');
        $this->postJson(route('payments.deduct'), ['paymentID' => $pending->id, 'deduction_amt' => 0])->assertStatus(422);
        $this->assertSame('Pending Consent', $pending->fresh()->status);

        $ok = $this->payment('Consented');
        $this->postJson(route('payments.deduct'), ['paymentID' => $ok->id, 'deduction_amt' => 40])->assertOk();
        $this->postJson(route('payments.deduct'), ['paymentID' => $ok->id, 'deduction_amt' => 30])->assertOk();
        $this->assertEquals([70, 'Partial Paid'], [$ok->fresh()->cash_paid, $ok->fresh()->status]);   // added, not overwritten
        $this->postJson(route('payments.deduct'), ['paymentID' => $ok->id, 'deduction_amt' => 31])->assertStatus(422);
    }

    public function test_other_resorts_employees_and_other_shops_products_are_out_of_reach()
    {
        $foreignEmp = Employee::where('resort_id', '!=', $this->shop->resort_id)->value('id');
        $foreignProduct = Product::where('shopkeeper_id', '!=', $this->shop->id)->first();

        $this->getJson(route('employees.details.get', $foreignEmp))->assertNotFound();
        $this->getJson(route('getProductPrice', ['product_id' => $foreignProduct->id]))->assertJson(['success' => false]);
        $this->putJson(route('shopkeeper.products.inlineUpdate', $foreignProduct->id), ['name' => 'Hacked', 'price' => 1, 'currency_type' => 'USD'])->assertNotFound();
        $this->assertNotSame('Hacked', $foreignProduct->fresh()->name);
        $this->postJson(route('shopkeeper.payment.store'), ['emp_id' => $foreignEmp, 'product_id' => $foreignProduct->id, 'quantity' => 1, 'price' => 1,
            'qr_code' => 'data:image/png;base64,AA'])->assertStatus(422)->assertJsonValidationErrors(['emp_id', 'product_id']);
    }

    public function test_uploads_must_be_real_images()   // NV08: names come from content, a script can't land under public/
    {
        $this->assertSame('png', \App\Helpers\Common::imageExtension(\Illuminate\Http\UploadedFile::fake()->image('photo.png')));
        $script = tempnam(sys_get_temp_dir(), 'probe');
        file_put_contents($script, "<?php echo 1;\n");   // a real upload is typed by content (a fake one by its name)
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        \App\Helpers\Common::imageExtension(new \Illuminate\Http\UploadedFile($script, 'probe.png', null, null, true));
    }

    public function test_mark_notification_only_touches_own_rows()
    {
        $foreign = DB::table('resort_notifications')->insertGetId(['resort_id' => $this->shop->resort_id, 'user_id' => 0, 'module' => 'People', 'type' => 1,
            'message' => 'x', 'status' => 'unread', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson(route('shopkeeper.notifications.mark'), ['id' => $foreign])->assertOk();
        $this->assertSame('unread', DB::table('resort_notifications')->where('id', $foreign)->value('status'));
    }
}
