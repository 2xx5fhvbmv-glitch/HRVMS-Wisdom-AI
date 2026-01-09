<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('order');
            $table->timestamps();
        });

        Schema::create('admin_module_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('module_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->timestamps();

        });

        Schema::create('admin_role_module_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('role_id');
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
        Schema::dropIfExists('admin_role_module_permissions');
        Schema::dropIfExists('admin_module_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('admin_modules');
    }
}
