<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Super-admin "Resort Data Setup": load a new client resort's master data,
 * staff and attendance from the exports of their previous HR system.
 *
 * resort_data_imports         — one upload batch per resort (files + their
 *                               column mapping, admin-chosen options, last
 *                               dry-run / import report).
 * resort_data_import_mappings — column mappings remembered by header
 *                               fingerprint, so the AI is only asked once
 *                               per export layout, never per upload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resort_data_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->index();
            $table->string('status', 20)->default('draft'); // draft | imported
            $table->json('files')->nullable();
            $table->json('options')->nullable();
            $table->json('report')->nullable();
            // Queued work (RunResortDataImport): analyse | validate | import.
            $table->string('job_action', 20)->nullable();
            $table->string('job_status', 20)->nullable(); // queued | running | done | failed
            $table->text('job_message')->nullable();
            $table->timestamp('job_started_at')->nullable();
            // New logins' temporary passwords, encrypted, kept only until the admin clears them.
            $table->text('credentials')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // admins.id
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('resort_data_import_mappings', function (Blueprint $table) {
            $table->id();
            $table->char('fingerprint', 40)->unique();
            $table->json('mapping');
            $table->string('source', 10); // ai | manual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resort_data_import_mappings');
        Schema::dropIfExists('resort_data_imports');
    }
};
