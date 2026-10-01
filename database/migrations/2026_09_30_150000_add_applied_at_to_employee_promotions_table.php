<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PE-05: confirmPromotion() had no way to tell it had already been
     * applied, so calling it twice on the same Approved promotion silently
     * re-wrote the employee's rank/department/position/salary a second
     * time. Stamped once, on the confirm that actually applies it.
     */
    public function up(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->timestamp('applied_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->dropColumn('applied_at');
        });
    }
};
