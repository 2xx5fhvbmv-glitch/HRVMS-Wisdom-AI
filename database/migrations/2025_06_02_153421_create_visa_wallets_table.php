<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisaWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('WalletName')->nullable();
            $table->decimal('Amt', 15, 2)->default(0.00);
            $table->date('Payment_Date')->nullable();
            $table->text('comments')->nullable();
            $table->string('file')->nullable();

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
        Schema::dropIfExists('visa_wallets');
    }
}
