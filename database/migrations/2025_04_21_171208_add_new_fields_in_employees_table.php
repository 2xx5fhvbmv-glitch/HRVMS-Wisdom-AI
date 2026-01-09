<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsInEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify ENUM 'status' using raw SQL to avoid Doctrine issues
        DB::statement("ALTER TABLE employees MODIFY status ENUM('Active','Inactive','Terminated','Resigned','On Leave','Suspended') DEFAULT 'Active'");

        // Add or update other fields
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('title', ['Mr', 'Miss', 'Mrs'])->default('Mr')->after('id');
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->default('Single')->after('dob');
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable()->after('marital_status');
            $table->enum('employment_type', ['Full-Time', 'Part-Time', 'Contract', 'Casual', 'Probationary', 'Internship', 'Temporary'])->default('Full-Time')->after('joining_date');
            $table->string('passport_number')->nullable()->after('employment_type');
            $table->string('nid', 50)->nullable()->after('passport_number');
            $table->text('present_address')->nullable()->after('nid');
            $table->string('biometric_file')->nullable()->after('present_address');
            $table->string('tin')->nullable()->after('biometric_file');
            $table->string('contract_type')->nullable()->after('tin');
            $table->date('termination_date')->nullable()->after('contract_type');
            $table->enum('payment_mode',['Cash','Bank'])->default('Cash')->after('termination_date');
            $table->string('bank_name')->nullable()->after('payment_mode');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('account_type')->nullable()->after('bank_branch');
            $table->string('IFSC_BIC')->nullable()->after('account_type');
            $table->string('account_holder_name')->nullable()->after('IFSC_BIC');
            $table->string('account_no')->nullable()->after('account_holder_name');
            $table->enum('currency',['USD','MVR'])->default('MVR')->after('account_no');
            $table->string('IBAN')->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // You can optionally reverse these changes here
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'marital_status',
                'blood_group',
                'employment_type',
                'passport_number',
                'nid',
                'present_address',
                'biometric_file',
                'tin',
                'contract_type',
                'termination_date',
                'payment_mode',
                'bank_name',
                'bank_branch',
                'account_type',
                'IFSC_BIC',
                'account_holder_name',
                'account_no',
                'currency',
                'IBAN'
            ]);
        });

        // Revert enum modification if needed
        DB::statement("ALTER TABLE employees MODIFY status ENUM('Active','Inactive') DEFAULT 'Active'");
    }
}
