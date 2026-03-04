<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->enum('availability_status', ['pending', 'available', 'unavailable'])->nullable()->after('consent_responded_at');
            $table->string('availability_token')->nullable()->unique()->after('availability_status');
            $table->timestamp('availability_responded_at')->nullable()->after('availability_token');
        });
    }

    public function down()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->dropColumn(['availability_status', 'availability_token', 'availability_responded_at']);
        });
    }
};
