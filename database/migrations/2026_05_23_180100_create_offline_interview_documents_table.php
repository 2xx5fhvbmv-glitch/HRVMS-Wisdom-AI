<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-file uploads for the offline interview wizard:
 *   - Step 3 "Upload Candidate Documents" (CV + other docs)
 *   - Step 4 per-round scanned documents (HR / HOD / GM round attachments)
 *
 * `category` distinguishes them so we can list them per-step in the wizard
 * and on the details page.
 *   - 'documents'     — generic candidate docs (Step 3)
 *   - 'hr_round'      — HR interview round scanned doc
 *   - 'hod_round'     — HOD round
 *   - 'gm_round'      — GM round
 *   - 'offer_letter'  — offer letter (also stored on offline_interviews
 *                      for the headline; this row keeps the file metadata)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_interview_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('offline_interview_id');
            $table->enum('category', ['documents','hr_round','hod_round','gm_round','offer_letter'])
                ->default('documents');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['offline_interview_id', 'category']);
            $table->foreign('offline_interview_id')
                ->references('id')->on('offline_interviews')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_interview_documents');
    }
};
