<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSecureColumnToChildFileManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('child_file_management', function (Blueprint $table) {
            $table->boolean('is_secure')->default(false)->after('NewFileName');
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
            $table->dropColumn('is_secure');
        });
    }
}
