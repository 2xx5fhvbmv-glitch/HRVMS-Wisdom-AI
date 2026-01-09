<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddfieldMaintanaceRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->text('RejactionReason')->nullable()->after('Status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->date('RejactionReason')->nullable()->after('Status');
        });
    }
}
