<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantWiseStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_wise_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Applicant_id')->default(0);
            $table->string('As_ApprovedBy')->nullable();
            $table->enum('status',[
                                                    'Sortlisted By Wisdom AI',
                                                    'Rejected By Wisdom AI',
                                                    'Sortlisted',
                                                    'Round',
                                                    'Rejected',
                                                    'Selected'])->nullable();
            $table->timestamps();
            $table->foreign('Applicant_id')->references('id')->on('applicant_form_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applicant_wise_statuses');
    }
}
