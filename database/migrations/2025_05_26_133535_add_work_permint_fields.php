<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkPermintFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('work_permit_medical_renewals', function (Blueprint $table) {
            $table->string('Currency')->nullable()->after('Cost');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
          Schema::table('work_permit_medical_renewals', function (Blueprint $table) {
            $table->dropColumn('Currency');
        });
    }
}
