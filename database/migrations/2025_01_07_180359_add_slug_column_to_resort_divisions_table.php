<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlugColumnToResortDivisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
               if (!Schema::hasColumn('resort_divisions', 'slug')) {
                    Schema::table('resort_divisions', function (Blueprint $table) {
                        $table->string('slug')->nullable();
                    });
                }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('resort_divisions', 'slug')) {
            Schema::table('resort_divisions', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
}
