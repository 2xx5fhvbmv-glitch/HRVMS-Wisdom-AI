<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermanentCurrentAddressToEmployeesTable extends Migration
{
    /**
     * present_address is one field but the Job Description legal template
     * (Employment Act s.15) requires Permanent Address and Current Address
     * as two distinct fields — neither existed on employees before this.
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('permanent_address')->nullable()->after('present_address');
            $table->text('current_address')->nullable()->after('permanent_address');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['permanent_address', 'current_address']);
        });
    }
}
