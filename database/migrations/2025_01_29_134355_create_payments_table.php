<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shopkeeper_id');
            $table->string('order_id');
            $table->unsignedInteger('emp_id');
            $table->date('purchased_date');
            $table->unsignedInteger('product_id');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['Consent Send', 'Consented','Pending Consent','Pending','Approved','Paid'])->nullable();
            $table->timestamps();

            $table->foreign('shopkeeper_id')->references('id')->on('shopkeepers')->onDelete('cascade');
            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
