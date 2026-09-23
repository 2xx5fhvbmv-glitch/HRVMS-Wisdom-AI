<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees_leaves', function (Blueprint $table) {
            // Per-application paid/unpaid override for Casual/Intern leave —
            // the same leave category can be paid for one Casual/Intern and
            // unpaid for another at the same resort, so leave_categories.is_paid
            // alone isn't enough for this employment category. Always null for
            // Permanent applications (their category's is_paid decides, as
            // today); always set (paid/unpaid) for Casual/Intern applications.
            $table->enum('is_paid_override', ['paid', 'unpaid'])->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_leaves', function (Blueprint $table) {
            $table->dropColumn('is_paid_override');
        });
    }
};
