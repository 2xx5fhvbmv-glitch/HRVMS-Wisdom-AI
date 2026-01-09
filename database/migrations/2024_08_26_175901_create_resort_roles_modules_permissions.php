<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortRolesModulesPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_roles_modules_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('resort_id');
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
        Schema::dropIfExists('resort_roles_modules_permissions');
    }
}
