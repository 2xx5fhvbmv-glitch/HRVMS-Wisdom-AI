<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTravelTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('travel_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id'); // Foreign key to resorts
            $table->unsignedInteger('leave_request_id')->nullable(); // Link to leave requests
            $table->unsignedInteger('employee_id'); // Link to employees
            $table->string('ticket_file_path'); // Path to the uploaded ticket
            $table->enum('status', ['Pending', 'Sent'])->default('Pending'); // Status of the ticket
            $table->unsignedBigInteger('uploaded_by'); // HR who uploaded the ticket
            $table->timestamps();

            // Foreign key constraints (optional, add if these tables exist)
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('leave_request_id')->references('id')->on('employees_leaves');
            $table->foreign('employee_id')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('travel_tickets');
    }
}
