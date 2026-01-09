<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortBenifitGridTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_benifit_grid', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('resort_id');
            $table->string('emp_grade');
            $table->string('rank');
            $table->string('contract_status');
            $table->string('effective_date');
            $table->string('salary_period')->nullable();
            $table->integer('service_charge')->nullable();
            $table->integer('ramadan_bonus')->nullable();
            $table->string('uniform')->nullable();
            $table->enum('health_care_insurance', ['yes', 'no'])->default('yes');
            $table->integer('day_off_per_week')->nullable();
            $table->integer('working_hrs_per_week')->nullable();
            $table->integer('emergency_leave')->nullable();
            $table->integer('birthday_leave')->nullable();
            $table->integer('public_holiday_per_year')->nullable();
            $table->integer('paid_seak_leave_per_year')->nullable();
            $table->integer('paid_companssionate_leave_per_year')->nullable();
            $table->integer('paid_maternity_leave_per_year')->nullable();
            $table->integer('paid_paternity_leave_per_year')->nullable();
            $table->integer('paid_worked_public_holiday_and_friday')->nullable();
            $table->enum('relocation_ticket', ['yes', 'no'])->default('yes');
            $table->integer('max_excess_luggage_relocation_expense')->nullable();
            $table->enum('ticket_upon_termination', ['yes', 'no'])->default('yes');
            $table->integer('meals_per_day')->nullable();
            $table->string('accommodation_status')->nullable();
            $table->enum('furniture_and_fixtures', ['yes', 'no'])->default('yes');
            $table->string('housekeeping')->nullable();
            $table->string('linen')->nullable();
            $table->string('laundry')->nullable();
            $table->enum('internet_access', ['yes', 'no'])->default('yes');
            $table->enum('telephone', ['yes', 'no'])->default('yes');
            $table->integer('annual_leave')->nullable();
            $table->enum('annual_leave_ticket', ['yes', 'no'])->default('yes');
            $table->integer('rest_and_relaxation_leave_per_year')->nullable();
            $table->integer('no_of_r_and_r_leave')->nullable();
            $table->integer('total_rest_and_relaxation_leave_per_year')->nullable();
            $table->integer('rest_and_relaxation_allowance')->nullable();
            $table->integer('paid_circumcision_leave_per_year')->nullable();
            $table->enum('overtime', ['yes', 'n/a'])->default('yes');
            $table->enum('salary_paid_in', ['USD', 'MVR'])->default('MVR');
            $table->enum('loan_and_salary_advanced', ['yes', 'n/a'])->default('yes');
            $table->string('sports_and_entertainment_facilities')->nullable();
            $table->integer('free_return_flight_to_male_per_year')->nullable();
            $table->integer('food_and_beverages_discount')->nullable();
            $table->integer('alchoholic_beverages_discount')->nullable();
            $table->integer('spa_discount')->nullable();
            $table->integer('dive_center_discount')->nullable();
            $table->integer('water_sports_discount')->nullable();
            $table->integer('friends_with_benefit_discount')->nullable();
            $table->integer('standard_staff_rate_for_single')->nullable();
            $table->integer('standard_staff_rate_for_double')->nullable();
            $table->integer('staff_rate_for_seaplane_male')->nullable();
            $table->integer('male_subsistence_allowance')->nullable();
            $table->string('status');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_benifit_grid');
    }
}
