<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('title');
            $table->date('date');
            $table->time('time');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->integer('reminder_days')->default(7);
            $table->enum('events_for',['organization','department','employee']);
            $table->string('employees')->nullable();
            $table->enum('status',['pending','accept','decline'])->default('pending');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
