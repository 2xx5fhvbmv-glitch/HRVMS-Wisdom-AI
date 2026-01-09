<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsincompliances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('compliances', function (Blueprint $table) {
           $table->enum('Dismissal_status', ['Pending', 'Rejected'])->after('status')->default('Pending')->nullable();
       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('compliances', function (Blueprint $table) {
           $table->dropColumn('date');
        });
    }
}
