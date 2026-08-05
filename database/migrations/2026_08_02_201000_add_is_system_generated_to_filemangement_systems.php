<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Common::AWSEmployeeFileUpload() auto-creates a subfolder (MaintanceRequest,
// GrivanceAttachments, RequestAttachments, HousekeepingImages, etc.) directly
// under an employee's own root folder for every module that stores mobile
// attachments this way — with the exact same shape as a folder the employee
// makes themselves via createFolder(), so My Drive had no way to tell them
// apart and surfaced backend/system folders the employee never created.
return new class extends Migration
{
    public function up()
    {
        Schema::table('filemangement_systems', function (Blueprint $table) {
            $table->boolean('is_system_generated')->default(false)->after('Folder_Type');
        });
    }

    public function down()
    {
        Schema::table('filemangement_systems', function (Blueprint $table) {
            $table->dropColumn('is_system_generated');
        });
    }
};
