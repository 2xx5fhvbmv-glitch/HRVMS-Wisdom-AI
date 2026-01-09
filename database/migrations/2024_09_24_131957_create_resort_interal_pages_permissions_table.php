<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortInteralPagesPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_interal_pages_permissions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('resort_id');
            $table->unsignedInteger('Dept_id');
            $table->unsignedInteger('position_id');

            $table->unsignedInteger('Permission_id');
            $table->timestamps();

            $table->foreign('Dept_id')->references('id')->on('resort_departments');
            $table->foreign('position_id')->references('id')->on('resort_positions');

            $table->unsignedInteger('page_id');
            $table->foreign('page_id')
                     ->references('id')->on('resort_departments');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_interal_pages_permissions');
    }
}
