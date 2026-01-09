<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToResortBenifitGridTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_benifit_grid', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('male_subsistence_allowance');
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
            $table->dropColumn('custom_fields');
        });
    }
}
