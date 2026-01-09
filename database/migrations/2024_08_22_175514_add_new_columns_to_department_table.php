<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToDepartmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('department', function (Blueprint $table) {
            // Add the division_id column
            $table->unsignedInteger('division_id')->nullable()->after('id');

            // Add the foreign key constraint
            $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('department', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['division_id']);
            
            // Drop the division_id column
            $table->dropColumn('division_id');
        });
    }
}
