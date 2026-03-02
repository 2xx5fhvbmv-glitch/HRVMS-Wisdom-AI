<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ta_template_extra_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('field_key', 100);
            $table->text('field_value')->nullable();
            $table->timestamps();

            $table->unique(['resort_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ta_template_extra_fields');
    }
};