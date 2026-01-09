<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id'); // Changed to BigInteger for consistency
            $table->unsignedBigInteger('Inv_Cat_id'); // Already BigInteger

            $table->string('ItemName')->nullable();
            $table->string('ItemCode')->nullable();
            $table->date('PurchageDate')->default(now()->format('Y-m-d')); // Default date in Y-m-d format
            $table->integer('Occupied')->nullable();
            $table->integer('Quantity')->nullable();
            $table->integer('MinMumStockQty')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Changed to BigInteger for possible referencing
            $table->unsignedBigInteger('modified_by')->nullable(); // Changed to BigInteger for possible referencing
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts'); // FK to resorts table
            $table->foreign('Inv_Cat_id')->references('id')->on('inventory_category_models'); // FK to inventory categories
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_modules');
    }
}
