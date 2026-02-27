<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_offer_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('applicant_status_id');
            $table->unsignedBigInteger('resort_id');
            $table->enum('type', ['offer_letter', 'contract']);
            $table->string('file_path')->nullable();
            $table->enum('status', ['Sent', 'Accepted', 'Rejected'])->default('Sent');
            $table->string('token')->unique();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_offer_contracts');
    }
};
