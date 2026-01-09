<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisaRenewalChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa_renewal_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visa_renewal_id');
            $table->string('Visa_Number')->nullable();
            $table->string('WP_No')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('visa_file')->nullable();
            $table->foreign('visa_renewal_id')->references('id')->on('visa_renewals')->onDelete('cascade');
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
        Schema::dropIfExists('visa_renewal_children');
    }
}
