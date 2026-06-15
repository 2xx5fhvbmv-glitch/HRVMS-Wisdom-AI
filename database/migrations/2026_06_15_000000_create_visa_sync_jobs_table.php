<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks asynchronous Xpat-sync extraction jobs. The upload endpoint creates a
 * row (status=processing) and returns its id immediately; the heavy OCR/LLM
 * work runs after the response is sent and writes the outcome back here, which
 * the page polls. Keeps the browser from waiting on slow extraction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('status')->default('processing'); // processing | done | failed
            $table->json('result')->nullable();               // the original {success,msg,errors} payload
            $table->timestamps();
            $table->index(['resort_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_sync_jobs');
    }
};
