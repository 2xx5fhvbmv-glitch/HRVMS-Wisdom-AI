<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToResortsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resorts', function (Blueprint $table) {
            $table->string('resort_id')->unique();
            $table->string('resort_it_email')->unique();
            $table->string('resort_it_phone')->nullable();
            $table->enum('same_billing_address', ['yes', 'no'])->default('yes');
            $table->text('billing_address1')->nullable();
            $table->text('billing_address2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_pincode')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('tin')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('invoice_email')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('due_date')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('service_package')->nullable();
            $table->string('contract_start_date')->nullable();
            $table->string('contract_end_date')->nullable();
            $table->integer('no_of_users')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resorts', function (Blueprint $table) {
            $table->dropColumn('resort_id');
            $table->dropColumn('resort_it_email');
            $table->dropColumn('resort_it_phone');
            $table->dropColumn('same_billing_address');
            $table->dropColumn('billing_address1');
            $table->dropColumn('billing_address2');
            $table->dropColumn('billing_city');
            $table->dropColumn('billing_state');
            $table->dropColumn('billing_pincode');
            $table->dropColumn('billing_country');
            $table->dropColumn('tin');
            $table->dropColumn('payment_method');
            $table->dropColumn('invoice_email');
            $table->dropColumn('payment_status');
            $table->dropColumn('due_date');
            $table->dropColumn('invoice_status');
            $table->dropColumn('service_package');
            $table->dropColumn('contract_start_date');
            $table->dropColumn('contract_end_date');
            $table->dropColumn('no_of_users');
        });
    }
}
