<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('performance_meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('resort_id');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('reason')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('meeting_id')->references('id')->on('peformance_meetings')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_meeting_participants');
    }
};
