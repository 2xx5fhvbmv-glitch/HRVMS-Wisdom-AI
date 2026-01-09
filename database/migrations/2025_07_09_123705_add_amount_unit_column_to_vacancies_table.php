<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountUnitColumnToVacanciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->enum('amount_unit', ['MVR', 'USD'])->default('MVR')->after('service_provider_name');
            $table->enum('is_required_local', ['Yes', 'No'])->default('No')->after('recruitment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn('amount_unit');
            $table->dropColumn('is_required_local');
        });
    }
}
