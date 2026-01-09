<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Addemployeesfields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!DB::getSchemaBuilder()->hasColumn('employees', 'division_id')) {
                $table->integer('division_id')->after('Emp_id');
            }
        });




        Schema::table('resort_divisions', function (Blueprint $table) {
            if (!DB::getSchemaBuilder()->hasColumn('resort_divisions', 'slug')) {
                $table->integer('slug')->after('short_name');
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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('division_id');
        });


        Schema::table('resort_divisions', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
