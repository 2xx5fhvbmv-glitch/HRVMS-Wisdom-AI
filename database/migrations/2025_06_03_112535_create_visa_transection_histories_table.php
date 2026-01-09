<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisaTransectionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa_transection_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('transaction_id')->unique();
            $table->unsignedBigInteger('to_wallet')->nullable();
            $table->unsignedBigInteger('from_wallet')->nullable();
            $table->decimal('Amt', 15, 2)->default(0.00);
            $table->decimal('to_wallet_realAmt', 15, 2)->nullable();
            $table->decimal('from_wallet_realAmt', 15, 2)->nullable();
            $table->date('Payment_Date')->nullable();
            $table->string('file')->nullable();
            $table->text('comments')->nullable();
            $table->foreign('to_wallet')->references('id')->on('visa_wallets')->onDelete('cascade');
            $table->foreign('from_wallet')->references('id')->on('visa_wallets')->onDelete('cascade');
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
        Schema::dropIfExists('visa_transection_histories');
    }
}
