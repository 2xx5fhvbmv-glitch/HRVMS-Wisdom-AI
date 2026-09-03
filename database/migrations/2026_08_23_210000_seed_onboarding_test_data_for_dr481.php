<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QA request (Onboarding Trello card): DR-481 (Ahmed Tholal, resort 26) was
 * reported as missing "so many fields" for onboarding testing. Checked
 * on_boarding_dashboard() directly for this employee — entry pass, flight
 * ticket, meetings, cultural insights, and HOD/department contacts were
 * already fully populated. The one genuine gap was accommodation (every
 * existing room in the resort was already at full capacity, so this adds a
 * fresh one rather than overbooking an existing room other testers may rely
 * on). Separately, the "Employee Handbook" File Management folder that
 * employeeHandbookList() looks for (by exact name, resort-wide) didn't exist
 * for ANY resort yet — that feature has been unable to show a single file
 * anywhere since it shipped. Seeded one there too so it's testable.
 */
return new class extends Migration
{
    private const RESORT_ID = 26;
    private const EMP_ID = 481;

    public function up()
    {
        $now = now();

        // --- Accommodation: new single room, not already at capacity ---
        $accId = DB::table('available_accommodation_models')->insertGetId([
            'resort_id'            => self::RESORT_ID,
            'BuildingName'         => 33,
            'Floor'                => 0,
            'RoomNo'               => '2',
            'Accommodation_type_id'=> 11, // Single Share
            'RoomType'             => 8,
            'BedNo'                => 1,
            'blockFor'             => 'Male',
            'Capacity'             => 1,
            'RoomStatus'           => 'Available',
            'Occupancytheresold'   => 100,
            'created_by'           => 259,
            'modified_by'          => 259,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        foreach ([2, 4, 6] as $itemId) { // Single Bed Frame, Mattress (Single), Wardrobe Cabinet
            DB::table('available_accommodation_inv_items')->insert([
                'Available_Acc_id' => $accId,
                'Item_id'          => $itemId,
                'quantity'         => 1,
            ]);
        }

        DB::table('assing_accommodations')->insert([
            'resort_id'      => self::RESORT_ID,
            'available_a_id' => $accId,
            'emp_id'         => self::EMP_ID,
            'BedNo'          => 1,
            'effected_date'  => $now->toDateString(),
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // --- Employee Handbook File Management folder (resort-wide) ---
        $handbookFolderId = DB::table('filemangement_systems')
            ->where('resort_id', self::RESORT_ID)
            ->where('Folder_Type', 'uncategorized')
            ->where('Folder_Name', 'Employee Handbook')
            ->value('id');

        if (!$handbookFolderId) {
            $handbookFolderId = DB::table('filemangement_systems')->insertGetId([
                'resort_id'           => self::RESORT_ID,
                'Folder_unique_id'    => bin2hex(random_bytes(5)),
                'UnderON'             => 0,
                'Folder_Name'         => 'Employee Handbook',
                'Folder_Type'         => 'uncategorized',
                'is_system_generated' => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        $alreadyHasFile = DB::table('child_file_management')
            ->where('Parent_File_ID', $handbookFolderId)
            ->exists();

        if (!$alreadyHasFile) {
            // Reuses the same already-uploaded, already-working S3 object as
            // the DR-481 entry-pass file (id 167) — a real file that
            // Common::GetAWSFile() already resolves correctly, so this is
            // immediately testable without a new binary upload.
            DB::table('child_file_management')->insert([
                'resort_id'        => self::RESORT_ID,
                'Parent_File_ID'   => $handbookFolderId,
                'File_Name'        => 'Employee Handbook 2026.pdf',
                'is_secure'        => 0,
                'File_Type'        => 'pdf',
                'File_Size'        => 131.26,
                'File_Path'        => '87fca1b014/public/categorized/sOP0WrhCwm/42cb18f7e4.pdf',
                'File_Extension'   => 'pdf',
                'File_Upload_By'   => 259,
                'File_Upload_Date' => $now->format('d/m/Y'),
                'File_Upload_Time' => $now->format('h:i A'),
                'File_Upload_IP'   => '127.0.0.1',
                'created_at'       => $now,
                'updated_at'       => $now,
                'unique_id'        => bin2hex(random_bytes(5)),
            ]);
        }
    }

    public function down()
    {
        $accId = DB::table('available_accommodation_models')
            ->where('resort_id', self::RESORT_ID)
            ->where('BuildingName', 33)
            ->where('RoomNo', '2')
            ->value('id');

        if ($accId) {
            DB::table('assing_accommodations')->where('available_a_id', $accId)->where('emp_id', self::EMP_ID)->delete();
            DB::table('available_accommodation_inv_items')->where('Available_Acc_id', $accId)->delete();
            DB::table('available_accommodation_models')->where('id', $accId)->delete();
        }

        $handbookFolderId = DB::table('filemangement_systems')
            ->where('resort_id', self::RESORT_ID)
            ->where('Folder_Name', 'Employee Handbook')
            ->value('id');

        if ($handbookFolderId) {
            DB::table('child_file_management')->where('Parent_File_ID', $handbookFolderId)->delete();
            DB::table('filemangement_systems')->where('id', $handbookFolderId)->delete();
        }
    }
};
