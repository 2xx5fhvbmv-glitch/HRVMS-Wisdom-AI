<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentrequestChildField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_request_children', function (Blueprint $table) {
            $table->enum('WorkPermitShow',['yes','no'])->default('no')->after('WorkPermitAmt');
           $table->enum('WorkPermitStep',['yes','no'])->default('no')->after('WorkPermitShow');
           
            $table->enum('QuotaslotShow',['yes','no'])->default('no')->after('QuotaslotAmt');
            $table->enum('QuotaslotStep',['yes','no'])->default('no')->after('QuotaslotShow');

            $table->enum('InsuranceShow',['yes','no'])->default('no')->after('InsurancePrimume');
            $table->enum('InsuranceStep',['yes','no'])->default('no')->after('InsuranceShow');

            $table->enum('MedicalReportShow',['yes','no'])->default('no')->after('MedicalReportFees');
            $table->enum('MedicalReportStep',['yes','no'])->default('no')->after('MedicalReportShow');

            $table->enum('VisaShow',['yes','no'])->default('no')->after('VisaAmt');
            $table->enum('VisaStep',['yes','no'])->default('no')->after('VisaShow');
            $table->enum('ChildStatus',['Pending','Complete'])->default('Pending')->after('VisaStep');
            $table->integer('OngoingSteps')->default(0)->after('ChildStatus');
            $table->integer('OverallSteps')->default(0)->after('OngoingSteps');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_request_children', function (Blueprint $table) {
            $table->dropColumn('WorkPermitShow');
            $table->dropColumn('QuotaslotShow');
            $table->dropColumn('InsuranceShow');
            $table->dropColumn('MedicalReportShow');
            $table->dropColumn('VisaShow');
            $table->dropColumn('WorkPermitStep');
            $table->dropColumn('QuotaslotStep');
            $table->dropColumn('InsuranceStep');
            $table->dropColumn('MedicalReportStep');
            $table->dropColumn('VisaStep');
        });
    }
}
