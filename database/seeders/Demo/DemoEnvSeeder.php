<?php

namespace Database\Seeders\Demo;

use App\Helpers\Common;
use App\Models\ResortAdmin;
use App\Support\Demo\Demo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Builds the Demo ENV resort's contents. Run by DemoReset after the purge, so
 * it always starts from an empty resort (the resorts row itself is kept, so a
 * client's name and logo survive a reset).
 *
 * Module seeders are added slice by slice; each receives the DemoContext.
 */
class DemoEnvSeeder
{
    public function seed(int $seed, ?callable $say = null): array
    {
        $say ??= fn () => null;
        mt_srand($seed); // every random choice below is reproducible from the printed seed

        $resortId = $this->resort();
        Demo::refresh();
        $admin = $this->masterAdmin($resortId);
        // 97 models stamp created_by from the resort-admin guard without checking
        // anyone is signed in — sign the master admin in for the whole seed.
        Auth::guard('resort-admin')->setUser($admin);

        $counts = ['menu pages' => $this->fullMenu($resortId)];
        $say('Resort, master login and menu ready.');

        try {
            Common::createFolderByResort($resortId);
        } catch (\Throwable $e) {
            Log::warning('Demo ENV: resort storage folder not created: ' . $e->getMessage());
        }

        return ['resort_id' => $resortId, 'counts' => $counts];
    }

    /** The resort row: created once, then kept (name/logo are the client's branding). */
    private function resort(): int
    {
        $code = config('demo.resort_code');
        if ($id = DB::table('resorts')->where('resort_id', $code)->value('id')) {
            return $id;
        }
        if (DB::table('resorts')->where('resort_prefix', config('demo.prefix'))->exists()) {
            throw new \RuntimeException('Another resort already uses the prefix ' . config('demo.prefix') . '.');
        }
        return DB::table('resorts')->insertGetId([
            'resort_name' => config('demo.resort_name'), 'resort_id' => $code, 'resort_prefix' => config('demo.prefix'),
            'resort_email' => config('demo.resort_email'), 'resort_it_email' => 'it@' . config('demo.login_domain'),
            'invoice_email' => 'finance@' . config('demo.login_domain'), 'resort_phone' => '+960 664 0000', 'status' => 'active',
            'address1' => 'Demo Island', 'city' => 'Malé', 'state' => 'Kaafu Atoll', 'country' => 'Maldives', 'zip' => '20026',
            'headoffice_address1' => 'Demo ENV Head Office', 'headoffice_city' => 'Malé', 'headoffice_country' => 'Maldives',
            'same_billing_address' => 'yes', 'payment_status' => 'paid', 'invoice_status' => 'draft', 'service_package' => 'package1',
            'contract_start_date' => now()->format('d-m-Y'), 'contract_end_date' => now()->addYears(5)->format('d-m-Y'),
            'no_of_users' => 500, 'support_preference' => 'Email', 'Support_SLA' => '24/7 support',
            'Position_access' => DB::table('positions')->where('position_title', 'Director Of Human Resources')->value('id'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function masterAdmin(int $resortId): ResortAdmin
    {
        $admin = new ResortAdmin;
        $admin->forceFill([
            'resort_id' => $resortId, 'first_name' => 'Demo', 'last_name' => 'Admin', 'gender' => 'male',
            'email' => 'admin@' . config('demo.login_domain'), 'password' => Hash::make(config('demo.password')),
            'type' => 'super', 'role_id' => 0, 'is_master_admin' => 1, 'is_employee' => 0, 'status' => 'active',
            'Position_access' => DB::table('resorts')->where('id', $resortId)->value('Position_access'),
            'must_change_password' => 0,
        ])->save();
        return $admin;
    }

    /** Every active page of every module (the demo shows the whole product). */
    private function fullMenu(int $resortId): int
    {
        $rows = DB::table('module_pages')->where('status', 'Active')->whereNull('deleted_at')->get(['id', 'Module_Id'])
            ->map(fn ($p) => ['resort_id' => $resortId, 'Module_id' => $p->Module_Id, 'page_permission_id' => $p->id,
                'created_at' => now(), 'updated_at' => now()])->all();
        DB::table('resort_pagewise_permissions')->insert($rows);
        return count($rows);
    }
}
