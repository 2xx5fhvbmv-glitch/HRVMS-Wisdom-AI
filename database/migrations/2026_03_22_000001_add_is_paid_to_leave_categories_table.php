<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPaidToLeaveCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('leave_categories', function (Blueprint $table) {
            $table->enum('is_paid', ['paid', 'unpaid'])->default('paid')->after('combine_with_other');
        });
    }

    public function down()
    {
        Schema::table('leave_categories', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
}
