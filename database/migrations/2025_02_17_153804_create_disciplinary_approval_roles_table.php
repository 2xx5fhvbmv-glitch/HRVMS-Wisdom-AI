<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplinaryApprovalRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinary_approval_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->integer('Approval_role_id')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts');
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
        Schema::dropIfExists('disciplinary_approval_roles');
    }
}
