<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInMonthlyCheckingModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_checking_models', function (Blueprint $table) {
            DB::statement("ALTER TABLE monthly_checking_models MODIFY COLUMN status ENUM('Pending','Conducted','Confirm','Rescheduled')");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monthly_checking_models', function (Blueprint $table) {
            DB::statement("ALTER TABLE monthly_checking_models MODIFY COLUMN status ENUM('Pending','Conducted','Confirm')");
        });
    }
}
