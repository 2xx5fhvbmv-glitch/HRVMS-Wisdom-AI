<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantFormDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void

     */
    public function up()
    {
        Schema::create('applicant_form_data', function (Blueprint $table) {
            $table->id();
            $table->integer('resort_id');
            $table->integer('Parent_v_id');
            $table->date('Application_date')->nullable();

            $table->string('passport_no')->nullable();
            $table->string('passport_img')->nullable();
            $table->string('passport_expiry_date')->nullable();
// /            $table->string('Visa_img')->nullable();
            $table->string('curriculum_vitae')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('full_length_photo')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('gender',['male','female','other'])->default('male');
            $table->date('dob')->nullable();
            $table->integer('mobile_number')->nullable();
            $table->string('email')->nullable();
            $table->enum('marital_status',['married','unmarried']);
            $table->integer('number_of_children')->nullable();
            $table->text('address_line_one')->nullable();
            $table->text('address_line_two')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('Joining_availability')->nullable();
            $table->string('reference')->nullable();
            $table->string('select_level')->nullable();
            $table->enum('terms_conditions',['yes','no']);
            $table->string('data_retention_month',100)->nullable();
            $table->string('data_retention_year',100)->nullable();
            $table->text('notes')->nullable();

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
        Schema::dropIfExists('applicant_form_data');
    }
}
