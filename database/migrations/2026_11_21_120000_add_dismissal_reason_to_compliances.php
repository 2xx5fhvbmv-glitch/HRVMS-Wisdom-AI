<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Why a breach was dismissed (Not a real breach / Approved exception / Already fixed outside the system).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliances', function (Blueprint $table) {
            $table->string('dismissal_reason', 255)->nullable()->after('Dismissal_status');
        });
    }

    public function down(): void
    {
        Schema::table('compliances', function (Blueprint $table) {
            $table->dropColumn('dismissal_reason');
        });
    }
};
