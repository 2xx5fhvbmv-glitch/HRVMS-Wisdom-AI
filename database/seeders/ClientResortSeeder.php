<?php

namespace Database\Seeders;

use App\Helpers\Common;
use App\Models\LeaveCategory;
use App\Models\ResortAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates an empty client resort ready for Admin → Resort Data Setup:
 * the same records ResortsController::store() makes (resort, master
 * resort admin, default storage folder, "Day Off" leave category), plus
 * the Demo Resort's module access so the portal menus are there to
 * configure. Department/position-level permissions are not copied — they
 * belong to another resort's departments.
 *
 * The password is printed to whoever runs the seeder (not emailed), so like
 * an admin who chose it via the "Set your password" link, there is no
 * forced change at first login.
 *
 * Safe to re-run: when the prefix already exists it only makes sure the
 * master admin is not stuck on a forced password change.
 *
 *   php artisan db:seed --class=ClientResortSeeder
 */
class ClientResortSeeder extends Seeder
{
    const PREFIX = 'HAY';
    const NAME = 'Hay Resort';
    const ADMIN_EMAIL = 'hayresort.admin@yopmail.com';
    const REFERENCE_RESORT_ID = 26; // Demo Resort: full module access

    public function run(): void
    {
        if ($existing = DB::table('resorts')->where('resort_prefix', self::PREFIX)->value('id')) {
            ResortAdmin::where('resort_id', $existing)->where('email', self::ADMIN_EMAIL)->update(['must_change_password' => false]);
            $this->command->warn('Resort with prefix ' . self::PREFIX . " already exists (id {$existing}) — only cleared the forced password change for " . self::ADMIN_EMAIL . '.');
            return;
        }
        $reference = DB::table('resorts')->where('id', self::REFERENCE_RESORT_ID)->first();

        $password = Common::generateUniquePassword(10);
        DB::transaction(function () use ($reference, $password, &$resortId) {
            // DB::table: the Resort model's saving hook needs a logged-in super admin.
            $resortId = DB::table('resorts')->insertGetId([
                'resort_name'          => self::NAME,
                'resort_prefix'        => self::PREFIX,
                'resort_id'            => substr(md5(uniqid(self::PREFIX, true)), 0, 10),
                'resort_email'         => 'hayresort@yopmail.com',
                'resort_phone'         => '9600000000',
                'resort_it_email'      => 'hayresort.it@yopmail.com',
                'resort_it_phone'      => '9600000001',
                'invoice_email'        => 'hayresort.invoice@yopmail.com',
                'status'               => 'active',
                'address1'             => 'Hay Island',
                'city'                 => $reference->city ?? 'Malé',
                'state'                => $reference->state ?? 'Kaafu Atoll',
                'country'              => 'Maldives',
                'zip'                  => '20000',
                'headoffice_address1'  => 'Hay Resort Head Office',
                'headoffice_city'      => 'Malé',
                'headoffice_country'   => 'Maldives',
                'same_billing_address' => 'yes',
                'payment_status'       => 'unpaid',
                'invoice_status'       => 'draft',
                'service_package'      => $reference->service_package ?? 'package1',
                'contract_start_date'  => now()->format('d-m-Y'),
                'contract_end_date'    => now()->addYear()->format('d-m-Y'),
                'no_of_users'          => 500,
                'support_preference'   => $reference->support_preference ?? 'Email',
                'Support_SLA'          => $reference->Support_SLA ?? '24/7 support',
                'Position_access'      => $reference->Position_access, // "Director Of Human Resources"
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $admin = new ResortAdmin;
            $admin->resort_id = $resortId;
            $admin->first_name = 'Hay';
            $admin->last_name = 'Admin';
            $admin->gender = 'male';
            $admin->email = self::ADMIN_EMAIL;
            $admin->status = 'active';
            $admin->Position_access = $reference->Position_access;
            $admin->role_id = 0;
            $admin->is_master_admin = 1;
            $admin->is_employee = 0;
            $admin->type = 'super';
            $admin->password = Hash::make($password);
            $admin->must_change_password = false;
            $admin->save();

            LeaveCategory::create([
                'resort_id' => $resortId, 'leave_type' => 'Day Off', 'number_of_days' => 52, 'carry_forward' => 1,
                'earned_leave' => 0, 'eligibility' => '8,1,2,3,4,5,6,7', 'frequency' => 'Weekly', 'number_of_times' => 1,
                'color' => '#f1c40f', 'leave_category' => '', 'combine_with_other' => 0, 'is_paid' => 'paid',
            ]);

            DB::table('resort_pagewise_permissions')->insert(
                DB::table('resort_pagewise_permissions')->where('resort_id', self::REFERENCE_RESORT_ID)->get(['Module_id', 'page_permission_id'])
                    ->map(fn ($p) => ['resort_id' => $resortId, 'Module_id' => $p->Module_id, 'page_permission_id' => $p->page_permission_id,
                        'created_at' => now(), 'updated_at' => now()])->all()
            );
        });

        Common::createFolderByResort($resortId);

        $this->command->info("Resort created: " . self::NAME . " (id {$resortId}, prefix " . self::PREFIX . ')');
        $this->command->info('Resort portal login: ' . url('/resort'));
        $this->command->info('Email:    ' . self::ADMIN_EMAIL);
        $this->command->info("Password: {$password}");
    }
}
