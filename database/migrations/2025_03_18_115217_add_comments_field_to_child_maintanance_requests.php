<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentsFieldToChildMaintananceRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('child_maintanance_requests', function (Blueprint $table) {
            $table->text('comments')->after('Status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('child_maintanance_requests', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
}
