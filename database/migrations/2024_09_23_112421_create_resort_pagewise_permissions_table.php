<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortPagewisePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_pagewise_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('resort_id');
            $table->unsignedBigInteger('Module_id');
            $table->unsignedBigInteger('page_permission_id');
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
        Schema::dropIfExists('resort_pagewise_permissions');
    }
}
