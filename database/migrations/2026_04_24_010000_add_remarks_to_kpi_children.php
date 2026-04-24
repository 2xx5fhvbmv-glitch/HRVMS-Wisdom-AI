<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_kpi_children', function (Blueprint $table) {
            if (!Schema::hasColumn('performance_kpi_children', 'remarks')) {
                $table->text('remarks')->nullable()->after('month');
            }
        });
    }

    public function down(): void
    {
        Schema::table('performance_kpi_children', function (Blueprint $table) {
            if (Schema::hasColumn('performance_kpi_children', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
