<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEligibleFieldsToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('bank_name');
            $table->dropColumn('bank_branch');
            $table->dropColumn('account_type');
            $table->dropColumn('IFSC_BIC');
            $table->dropColumn('account_holder_name');
            $table->dropColumn('account_no');
            $table->dropColumn('currency');
            $table->dropColumn('IBAN');
            $table->enum('entitled_service_charge', ['yes', 'no'])->default('no')->after('payment_mode');
            $table->enum('entitled_overtime', ['yes', 'no'])->default('no')->after('entitled_service_charge');
            $table->enum('entitled_public_holiday', ['yes', 'no'])->default('no')->after('entitled_overtime');
            $table->enum('ewt_status', ['yes', 'no'])->default('no')->after('entitled_public_holiday');
            $table->decimal('pension',8,2)->default(0.00)->after('ewt_status');
            $table->decimal('ewt',8,2)->default(0.00)->after('pension');
            $table->enum('basic_salary_currency', ['USD', 'MVR'])->default('USD')->after('basic_salary');
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
            $table->string('bank_name')->nullable()->after('payment_mode');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('account_type')->nullable()->after('bank_branch');
            $table->string('IFSC_BIC')->nullable()->after('account_type');
            $table->string('account_holder_name')->nullable()->after('IFSC_BIC');
            $table->string('account_no')->nullable()->after('account_holder_name');
            $table->string('currency')->nullable()->after('account_no');
            $table->string('IBAN')->nullable()->after('currency');
            $table->dropColumn('entitled_service_charge');
            $table->dropColumn('entitled_overtime');
            $table->dropColumn('entitled_public_holiday');
            $table->dropColumn('ewt_status');
            $table->dropColumn('pension');
            $table->dropColumn('ewt');
            $table->dropColumn('basic_salary_currency');

        });
    }
}
