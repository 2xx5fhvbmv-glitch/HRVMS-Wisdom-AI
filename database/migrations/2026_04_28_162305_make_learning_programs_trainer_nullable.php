<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_programs', function (Blueprint $table) {
            // External-trainer programs have no internal employee FK to point at,
            // so the column needs to allow NULL.
            $table->unsignedInteger('trainer')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('learning_programs', function (Blueprint $table) {
            $table->unsignedInteger('trainer')->nullable(false)->change();
        });
    }
};
