<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeNumericColumnsToDecimalInResortBenifitGridTable extends Migration
{
    // Columns that store money/rates/percentages -> decimal(10,2)
    // Columns that store counts/days/hours -> stay integer
    protected $decimalColumns = [
        'service_charge',
        'ramadan_bonus',
        'paid_worked_public_holiday_and_friday',
        'max_excess_luggage_relocation_expense',
        'rest_and_relaxation_allowance',
        'free_return_flight_to_male_per_year',
        'food_and_beverages_discount',
        'alchoholic_beverages_discount',
        'spa_discount',
        'dive_center_discount',
        'water_sports_discount',
        'friends_with_benefit_discount',
        'standard_staff_rate_for_single',
        'standard_staff_rate_for_double',
        'staff_rate_for_seaplane_male',
        'male_subsistence_allowance',
    ];

    public function up()
    {
        Schema::table('resort_benifit_grid', function (Blueprint $table) {
            foreach ($this->decimalColumns as $column) {
                $table->decimal($column, 10, 2)->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('resort_benifit_grid', function (Blueprint $table) {
            foreach ($this->decimalColumns as $column) {
                $table->integer($column)->nullable()->change();
            }
        });
    }
}
