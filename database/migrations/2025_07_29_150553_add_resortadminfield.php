<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResortadminfield extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_admins', function (Blueprint $table) {
            $table->integer('Position_access')->nullable()->after('resort_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
          Schema::table('resort_admins', function (Blueprint $table) {
            $table->dropColumn('Position_access');
        });
    }
}
