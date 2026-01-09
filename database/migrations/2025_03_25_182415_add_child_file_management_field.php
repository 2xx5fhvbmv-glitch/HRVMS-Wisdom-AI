<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChildFileManagementField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('child_file_management', function (Blueprint $table) {
            $table->string('unique_id')->nullable(); // Set default value
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('child_file_management', function (Blueprint $table) {
            $table->dropColumn('unique_id');
        });
    }
}
