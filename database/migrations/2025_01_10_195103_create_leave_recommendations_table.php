<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRecommendationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('leave_id');
            $table->unsignedInteger('recommended_by');
            $table->date('alt_start_date');
            $table->date('alt_end_date');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('leave_id')->references('id')->on('employees_leaves')->onDelete('cascade');
            $table->foreign('recommended_by')->references('id')->on('employees')->onDelete('cascade');
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_recommendations');
    }
}
