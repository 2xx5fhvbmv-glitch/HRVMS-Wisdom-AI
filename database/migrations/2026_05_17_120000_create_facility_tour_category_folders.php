<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * Creates the per-resort "facilityTourCategory" system folder in
 * filemangement_systems.
 *
 * People > Onboarding > Facility Tour Categories uploads images through
 * Common::AWSEmployeeFacilityCategoryImageUpload(), which resolves the
 * target folder with:
 *     where('resort_id', X)->where('Folder_Name', 'facilityTourCategory')
 * That folder is referenced only by FacilityTourCategoryController and is
 * never created by any migration or seeder — so for every resort the
 * helper hit "Folder does not exist", returned ['status' => false], and
 * every thumbnail/tour-image upload failed with
 * "Thumbnail image upload failed."
 *
 * UnderON = 0 (root) and Folder_Type = 'uncategorized' so it behaves as an
 * internal system folder and does not surface in the categorized file
 * manager UI.
 */
class CreateFacilityTourCategoryFolders extends Migration
{
    private const FOLDER = 'facilityTourCategory';

    public function up()
    {
        $now = Carbon::now();

        foreach (DB::table('resorts')->pluck('id') as $resortId) {
            $exists = DB::table('filemangement_systems')
                ->where('resort_id', $resortId)
                ->where('Folder_Name', self::FOLDER)
                ->exists();

            if ($exists) {
                continue;
            }

            // Folder_unique_id must not collide with an existing folder.
            do {
                $uniqueId = Str::random(10);
            } while (
                DB::table('filemangement_systems')
                    ->where('Folder_unique_id', $uniqueId)
                    ->exists()
            );

            DB::table('filemangement_systems')->insert([
                'resort_id'        => $resortId,
                'Folder_unique_id' => $uniqueId,
                'UnderON'          => 0,
                'Folder_Name'      => self::FOLDER,
                'Folder_Type'      => 'uncategorized',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table('filemangement_systems')
            ->where('Folder_Name', self::FOLDER)
            ->delete();
    }
}
