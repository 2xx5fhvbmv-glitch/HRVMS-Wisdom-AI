<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class ResortDivisionSlugchnage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_divisions', function (Blueprint $table) {
            // Add the 'slug' column if it does not already exist
            if (Schema::hasColumn('resort_divisions', 'slug')) {

                $table->string('slug', 250)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_divisions', function (Blueprint $table) {
            // Drop the 'slug' column if it exists
            if (Schema::hasColumn('resort_divisions', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
}
