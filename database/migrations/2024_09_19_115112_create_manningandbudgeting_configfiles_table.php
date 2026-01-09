<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManningandbudgetingConfigfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manningandbudgeting_configfiles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger( 'resort_id');

            $table->string('consolidatdebudget', 250)->nullable();
            $table->string('benifitgrid', 250)->nullable();
            $table->string('xpat', 100)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manningandbudgeting_configfiles');
    }
}
