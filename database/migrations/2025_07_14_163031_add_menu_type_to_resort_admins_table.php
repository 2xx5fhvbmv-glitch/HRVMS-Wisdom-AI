<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMenuTypeToResortAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_admins', function (Blueprint $table) {
            $table->enum('menu_type', ['horizontal','vertical'])->default('horizontal')->after('profile_picture');
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
            $table->dropColumn('menu_type');
        });
    }
}
