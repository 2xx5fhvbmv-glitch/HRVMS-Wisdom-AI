<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToResortAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_admins', function (Blueprint $table) {
            $table->enum('gender', ['male','female','other']);
            $table->string('personal_phone')->nullable();
            $table->string('status');
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
            $table->dropColumn('gender');
            $table->dropColumn('personal_phone');
            $table->dropColumn('status');
        });
    }
}
