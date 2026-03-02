<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ta_document_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->enum('type', ['offer_letter', 'contract']);
            $table->string('name', 255);
            $table->string('subject', 255)->nullable();
            $table->longText('content');
            $table->boolean('is_default')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ta_document_templates');
    }
};