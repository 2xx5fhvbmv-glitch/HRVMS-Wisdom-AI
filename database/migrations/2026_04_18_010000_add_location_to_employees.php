<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationToEmployees extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'location')) {
                $table->string('location', 50)->nullable()->after('nationality');
            }
        });

        // Backfill existing rows based on nationality
        \DB::statement("UPDATE employees SET location = CASE WHEN nationality = 'Maldivian' THEN 'Malé' WHEN nationality IS NOT NULL AND nationality != '' THEN 'Resorts' ELSE NULL END WHERE location IS NULL");
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
}
