<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldNameToEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('emg_cont_first_name')->nullable()->after('incremented_date');
            $table->string('emg_cont_last_name')->nullable()->after('emg_cont_first_name');
            $table->string('emg_cont_no')->nullable()->after('emg_cont_last_name');
            $table->string('emg_cont_alt_no')->nullable()->after('emg_cont_no');
            $table->string('emg_cont_relationship')->nullable()->after('emg_cont_alt_no');
            $table->string('emg_cont_email')->nullable()->after('emg_cont_relationship');
            $table->string('emg_cont_nationality')->nullable()->after('emg_cont_email');
            $table->string('emg_cont_dob')->nullable()->after('emg_cont_nationality');
            $table->string('emg_cont_age')->nullable()->after('emg_cont_dob');
            $table->string('emg_cont_education')->nullable()->after('emg_cont_age');
            $table->string('emg_cont_passport_no')->nullable()->after('emg_cont_education');
            $table->string('emg_cont_passport_expiry_date')->nullable()->after('emg_cont_passport_no');
            $table->text('emg_cont_current_address')->nullable()->after('emg_cont_passport_expiry_date');
            $table->text('emg_cont_permanent_address')->nullable()->after('emg_cont_current_address');
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
            $table->dropColumn('emg_cont_first_name');
            $table->dropColumn('emg_cont_last_name');
            $table->dropColumn('emg_cont_no');
            $table->dropColumn('emg_cont_alt_no');
            $table->dropColumn('emg_cont_relationship');
            $table->dropColumn('emg_cont_email');
            $table->dropColumn('emg_cont_nationality');
            $table->dropColumn('emg_cont_dob');
            $table->dropColumn('emg_cont_age');
            $table->dropColumn('emg_cont_education');
            $table->dropColumn('emg_cont_passport_no');
            $table->dropColumn('emg_cont_passport_expiry_date');
            $table->dropColumn('emg_cont_current_address');
            $table->dropColumn('emg_cont_permanent_address');
        });
    }
}
