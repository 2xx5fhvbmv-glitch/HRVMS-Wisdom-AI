<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKpiBonusToPayrollReviews extends Migration
{
    public function up()
    {
        Schema::table('payroll_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_reviews', 'kpi_bonus')) {
                $table->decimal('kpi_bonus', 12, 2)->default(0)->after('earnings_allowance');
            }
        });
    }

    public function down()
    {
        Schema::table('payroll_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_reviews', 'kpi_bonus')) {
                $table->dropColumn('kpi_bonus');
            }
        });
    }
}
