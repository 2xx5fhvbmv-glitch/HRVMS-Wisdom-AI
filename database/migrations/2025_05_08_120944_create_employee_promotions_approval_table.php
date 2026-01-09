<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePromotionsApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_promotions_approval', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->enum('status',['Approved','Rejected','Pending','On Hold'])->default('Pending');
            $table->enum('approval_rank',['Finance','GM']);
            $table->unsignedInteger('approved_by');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('promotion_id')->references('id')->on('employee_promotions')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('employees')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_promotions_approval');
    }
}
