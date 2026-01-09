<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shopkeeper_id');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->binary('qr_code')->nullable(); // QR code stored as a binary blob
            $table->timestamps();

            $table->foreign('shopkeeper_id')->references('id')->on('shopkeepers')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
