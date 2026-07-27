<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * clinic_appointment_categories was empty for every resort — the mobile
 * "Book Appointment" screen had nothing to select from, and the Clinic
 * Manager route to create categories (check.rank:CLINIC_STAFF) was itself
 * unreachable until the Position_Rank config fix in this same batch, so
 * nobody could have created any yet either way.
 *
 * Idempotent: skips any resort that already has categories, so re-running
 * (or a resort where a Clinic Manager already added their own) won't
 * create duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clinic_appointment_categories') || !Schema::hasTable('resorts')) {
            return;
        }

        $defaultCategories = [
            ['appointment_type' => 'General Checkup', 'color' => '#2E86AB'],
            ['appointment_type' => 'Follow-up', 'color' => '#5CB85C'],
            ['appointment_type' => 'Emergency', 'color' => '#D9534F'],
            ['appointment_type' => 'Vaccination', 'color' => '#F0AD4E'],
            ['appointment_type' => 'Medical Certificate', 'color' => '#9E5CF7'],
        ];

        $resortIds = DB::table('resorts')->pluck('id');
        $now = now();

        foreach ($resortIds as $resortId) {
            $exists = DB::table('clinic_appointment_categories')
                ->where('resort_id', $resortId)
                ->exists();
            if ($exists) {
                continue;
            }

            // Attribute the seed rows to the resort's own master admin
            // (created_by/modified_by have no FK but should still resolve
            // to a real person rather than a placeholder 0).
            $masterAdminId = DB::table('resort_admins')
                ->where('resort_id', $resortId)
                ->where('is_master_admin', 1)
                ->value('id')
                ?? DB::table('resort_admins')->where('resort_id', $resortId)->value('id');

            if (!$masterAdminId) {
                continue;
            }

            foreach ($defaultCategories as $category) {
                DB::table('clinic_appointment_categories')->insert([
                    'resort_id'        => $resortId,
                    'appointment_type' => $category['appointment_type'],
                    'color'            => $category['color'],
                    'created_by'       => $masterAdminId,
                    'modified_by'      => $masterAdminId,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('clinic_appointment_categories')) {
            return;
        }
        // Only remove the exact rows we seeded — leave any category a
        // Clinic Manager added by hand alone.
        DB::table('clinic_appointment_categories')
            ->whereIn('appointment_type', ['General Checkup', 'Follow-up', 'Emergency', 'Vaccination', 'Medical Certificate'])
            ->whereIn('color', ['#2E86AB', '#5CB85C', '#D9534F', '#F0AD4E', '#9E5CF7'])
            ->delete();
    }
};
