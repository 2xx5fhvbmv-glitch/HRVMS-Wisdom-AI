<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('resort_id'); //unique resort id which take form the resort
            $table->integer('Resort_role_id');
            $table->string('Emp_id');
            $table->unsignedInteger('Dept_id');

            $table->unsignedInteger('Position_id');

            $table->string('first_name',50);
            $table->string('middle_name',50)->nullable();
            $table->string('last_name',50);
            $table->string('email',50)->unique();
            $table->string('password',250);
            $table->string('remember_token')->nullable();
            $table->tinyInteger('is_employee')->default(1);
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city',30)->nullable();
            $table->string('state',30)->nullable();
            $table->string('zip',15)->nullable();
            $table->string('country',150)->nullable();
            $table->string('rank')->nullable();
            $table->string('profile_photo',250)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('Dept_id')
            ->references('id')->on('resort_departments');



            $table->foreign('Position_id')
            ->references('id')->on('resort_positions'); // Optional: update employee if position ID is updated

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
