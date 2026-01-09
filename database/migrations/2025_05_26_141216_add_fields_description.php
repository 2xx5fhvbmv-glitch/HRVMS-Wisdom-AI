<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            // First drop the column (no referencing it with "after")
            $table->dropColumn(['attechments']);
        });


        Schema::table('payroll_advance', function (Blueprint $table) {
           $table->enum('status',['Pending', 'Approved', 'Rejected', 'In-Progress'])->default('Pending')->after('pourpose');
           $table->enum('priority', ['High', 'Low','Medium'])->default('Low')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('priority');
            $table->json('attechments')->nullable();
        });
    }
}
