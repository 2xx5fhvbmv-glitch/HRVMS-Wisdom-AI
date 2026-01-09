<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('message_id');
            $table->unsignedBigInteger('Budget_id');
            $table->enum('status',['Genrated','Approved','Rejected','Pending','Completed'])->default('Genrated');
             $table->text('comments')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();

            $table->timestamps();

            $table->foreign('resort_id')
                    ->references('id')->on('resorts')
                    ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('Parent_msg_id')->on('resorts_child_notifications')
                ->onDelete('cascade');

            $table->foreign('Budget_id')
                ->references('id')->on('manning_responses')
                ->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budget_statuses');
    }
}
