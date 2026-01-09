<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SuperAdminAndSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // === SUPER ADMIN CREATION ===
        $adminId = DB::table('admins')->insertGetId([
            'first_name'      => 'Super',
            'middle_name'     => null,
            'last_name'       => 'Admin',
            'email'           => 'superadmin@wisdom.ai',
            'password'        => Hash::make('Admin@123'), 
            'role_id'         => 1, 
            'profile_picture' => null,
            'home_phone'      => null,
            'cell_phone'      => null,
            'start_date'      => $now,
            'address'         => 'Head Office',
            'sms'             => 1,
            'allow_login'     => 1,
            'notes'           => 'Default Super Admin account',
            'status'          => 'active',
            'type'            => 'super',
            'added_by'        => null,
            'created_by'      => 1,
            'modified_by'     => 1,
            'remember_token'  => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // === SITE SETTINGS ===
        DB::table('settings')->insert([
            'site_title'     => 'Wisdom AI HRVMS',
            'site_logo'      => null,
            'header_logo'    => null,
            'footer_logo'    => null,
            'admin_logo'     => null,
            'site_favicon'   => null,
            'email_address'  => null,
            'facebook_link'  => null,
            'instagram_link' => null,
            'youtube_link'   => null,
            'linkedin_link'  => null,
            'address_1'      => '123 Business Street, City Center',
            'address_2'      => 'Suite 401, Tech Park',
            'contact_number' => '9876543210',
            'website'        => 'https://wisdomai.com',
            'admin_email'    => 'admin@wisdomai.com',
            'support_email'  => 'support@wisdomai.com',
            'contents'       => 'Welcome to Wisdom AI HRVMS.',
            'date_format'    => 'd-m-Y',
            'time_format'    => 'H:i',
            'currency_symbol'=> '₹',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $this->command->info('✅ Super Admin and Settings data seeded successfully.');
    }
}
