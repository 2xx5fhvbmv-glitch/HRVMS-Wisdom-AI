<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEwtTaxBracketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ewt_tax_brackets', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('min_salary', 10, 2);
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->decimal('tax_rate', 5, 2);
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
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
        Schema::dropIfExists('ewt_tax_brackets');
    }
}
