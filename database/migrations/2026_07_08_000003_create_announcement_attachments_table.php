<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `announcement` table has no attachment support at all — the mobile
 * "Announcement Detail" screen needs an attachments[] list, so this adds a
 * simple child table referencing the existing generic file store
 * (child_file_management), the same pattern used elsewhere in the app
 * (e.g. EmployeesDocument.document_path).
 */
class CreateAnnouncementAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('announcement_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('announcement_id');
            $table->unsignedBigInteger('child_file_id');
            $table->timestamps();

            $table->foreign('announcement_id')->references('id')->on('announcement')->onDelete('cascade');
            $table->foreign('child_file_id')->references('id')->on('child_file_management')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('announcement_attachments');
    }
}
