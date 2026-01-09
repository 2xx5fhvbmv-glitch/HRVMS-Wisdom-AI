<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsReservedToResortPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            $table->enum('is_reserved', ['Yes', 'No'])->default('No')->after('status')->comment('Indicates if the position is reserved for a Local or Expat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            $table->dropColumn('is_reserved');
        });
    }
}
