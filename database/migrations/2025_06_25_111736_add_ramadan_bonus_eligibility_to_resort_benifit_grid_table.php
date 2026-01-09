<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRamadanBonusEligibilityToResortBenifitGridTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_benifit_grid', function (Blueprint $table) {
            $table->string('ramadan_bonus_eligibility')->nullable()->after('ramadan_bonus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_benifit_grid', function (Blueprint $table) {
            $table->dropColumn('ramadan_bonus_eligibility');
        });
    }
}
