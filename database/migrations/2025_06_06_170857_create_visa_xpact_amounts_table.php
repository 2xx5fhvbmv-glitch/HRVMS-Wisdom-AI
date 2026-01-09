<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisaXpactAmountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa_xpact_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Xpact_WalletName')->nullable();
            $table->decimal('Xpact_Amt', 15, 2)->default(0.00);
            $table->date('Xpact_Payment_Date')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
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
        Schema::dropIfExists('visa_xpact_amounts');
    }
}
