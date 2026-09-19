<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §35 — schema for the Casual payroll run:
 * - resort_site_settings.casual_payment_model: which model this resort
 *   uses (lump-sum via service provider = no payroll run at all here;
 *   direct-pay = the Casual run below applies). Default lump_sum so every
 *   existing resort keeps today's behavior (no Casual payroll UI) until
 *   HR explicitly opts in.
 * - casual_position_pay_configs: one row per Casual resort_positions row
 *   (employee_category='Casual') — basic salary + service-provider
 *   commission, each with its OWN currency (the founder was explicit these
 *   can differ). Configured once per position, not per employee/month.
 * - payroll.payroll_category: which run a payroll row belongs to. NULL/
 *   'Permanent' = today's run (Permanent+Intern); 'Casual' = this one.
 *   Existing rows all resolve to 'Permanent' via the default.
 * - payroll_reviews.service_provider_commission: tracked alongside the
 *   payslip for the resort's own records (what they owe the agency) —
 *   never added into net_salary, which stays employee-only pay.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('resort_site_settings', function (Blueprint $table) {
            $table->enum('casual_payment_model', ['lump_sum', 'direct_pay'])->default('lump_sum')->after('currency');
        });

        Schema::create('casual_position_pay_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('position_id');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->enum('basic_salary_currency', ['USD', 'MVR'])->default('USD');
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->enum('commission_currency', ['USD', 'MVR'])->default('USD');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('position_id')->references('id')->on('resort_positions');
            $table->unique(['resort_id', 'position_id']);
        });

        Schema::table('payroll', function (Blueprint $table) {
            $table->enum('payroll_category', ['Permanent', 'Casual'])->default('Permanent')->after('resort_id');
        });

        Schema::table('payroll_reviews', function (Blueprint $table) {
            $table->decimal('service_provider_commission', 10, 2)->nullable()->after('service_charge');
        });
    }

    public function down()
    {
        Schema::table('payroll_reviews', function (Blueprint $table) {
            $table->dropColumn('service_provider_commission');
        });
        Schema::table('payroll', function (Blueprint $table) {
            $table->dropColumn('payroll_category');
        });
        Schema::dropIfExists('casual_position_pay_configs');
        Schema::table('resort_site_settings', function (Blueprint $table) {
            $table->dropColumn('casual_payment_model');
        });
    }
};
