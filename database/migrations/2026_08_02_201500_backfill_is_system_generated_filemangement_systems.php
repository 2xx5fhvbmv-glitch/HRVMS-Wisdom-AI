<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Flags existing subfolder rows created by AWSEmployeeFileUpload's $SubFolder
// path (before is_system_generated existed) — every distinct name currently
// passed as $SubFolder anywhere in the codebase, grepped from every
// Common::AWSEmployeeFileUpload(...) call site.
return new class extends Migration
{
    private array $systemFolderNames = [
        'EmployeesChatAttachments',
        'EmployeesDocument',
        'GrivanceAttachments',
        'HousekeepingImages',
        'IncidentAttatchements',
        'LeaveAttachments',
        'MaintanceRequest',
        'RequestAttachments',
        'ResignationAttachments',
        'clinicMedicalCertificateAttachments',
        'clinicTreatmentAttachment',
        'employeeSelfie',
    ];

    public function up()
    {
        DB::table('filemangement_systems')
            ->whereIn('Folder_Name', $this->systemFolderNames)
            ->update(['is_system_generated' => true]);
    }

    public function down()
    {
        DB::table('filemangement_systems')
            ->whereIn('Folder_Name', $this->systemFolderNames)
            ->update(['is_system_generated' => false]);
    }
};
