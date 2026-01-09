<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('resort_id');
            $table->string('name');
            $table->enum('status',['active','inactive'])->default('active');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('resort_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('resort_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('order');
            $table->timestamps();
        });

        Schema::create('resort_module_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('module_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->timestamps();
        });

        Schema::create('resort_position_module_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('module_permission_id');
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
        Schema::dropIfExists('resort_modules');
        Schema::dropIfExists('resort_position_module_permissions');
        Schema::dropIfExists('resort_module_permissions');
        Schema::dropIfExists('resort_permissions');
    }
}
