<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkpermitField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('work_permits', function (Blueprint $table) 
        {
            $table->string('ReceiptNumber')->nullable()->after('Reciept_file');
            $table->enum('PaymentType',['Lumpsum','Installment'])->default('Installment')->after('ReceiptNumber');
            $table->string('Work_Permit_Number')->nullable()->after('Due_Date');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('work_permits', function (Blueprint $table) 
        {
                $table->dropColumn('ReceiptNumber');
                $table->dropColumn('PaymentType');
                $table->dropColumn('Work_Permit_Number');
        });
    }
}
