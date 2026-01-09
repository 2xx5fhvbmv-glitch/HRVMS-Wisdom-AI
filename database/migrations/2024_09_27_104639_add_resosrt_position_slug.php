<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResosrtPositionSlug extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('status');


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
            // Rollback the addition of Admin_Parent_id if needed
            $table->dropColumn('slug');
        });
    }
}
