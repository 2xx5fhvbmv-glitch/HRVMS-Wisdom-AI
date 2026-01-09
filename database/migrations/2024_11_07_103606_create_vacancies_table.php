<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVacanciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('budgeted');
            $table->unsignedInteger('Resort_id');
            $table->unsignedInteger('department');
            $table->date('required_starting_date');
            $table->unsignedInteger('position');
            $table->unsignedInteger('reporting_to');
            $table->integer('rank');
            $table->unsignedInteger('division');
            $table->unsignedInteger('section')->nullable();
            $table->string('employee_type');
            $table->string('service_provider_name')->nullable();
            $table->decimal('salary',12, 2);
            $table->string('food')->nullable();
            $table->string('accomodation')->nullable();
            $table->string('transportation')->nullable();
            $table->decimal('budgeted_salary',12, 2);
            $table->decimal('propsed_salary',12, 2);
            $table->string('budgeted_accomodation')->nullable();
            $table->decimal('allowance',12, 2)->nullable();
            $table->enum('service_charge',['YES', 'NO']);
            $table->enum('uniform',['YES', 'NO']);
            $table->decimal('medical',12, 2)->nullable();
            $table->decimal('insurance',12, 2)->nullable();
            $table->decimal('pension',12, 2)->nullable();
            $table->string('recruitment');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('reporting_to')->references('id')->on('employees');
            $table->foreign('division')->references('id')->on('resort_divisions');
            $table->foreign('position')->references('id')->on('resort_positions');
            $table->foreign('department')->references('id')->on('resort_departments');
            $table->foreign('section')->references('id')->on('resort_sections');

            $table->foreign('Resort_id')->references('id')->on('resorts');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vacancies');
    }
}
