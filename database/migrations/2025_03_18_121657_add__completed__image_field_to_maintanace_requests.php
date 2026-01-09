<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompletedImageFieldToMaintanaceRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->string('Completed_Image')->nullable()->after('Image'); 
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
            $table->dropColumn('Completed_Image');
        });
    }
}
