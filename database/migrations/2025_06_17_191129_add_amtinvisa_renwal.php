<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmtinvisaRenwal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visa_renewals', function (Blueprint $table) {
           $table->decimal('Amt', 15, 2)->default(0.00)->after('visa_file');
        });

        Schema::table('visa_renewal_children', function (Blueprint $table) {
           $table->decimal('Amt', 15, 2)->default(0.00)->after('visa_file');
        });

   

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
          Schema::table('visa_renewals', function (Blueprint $table) {
           $table->dropColumn('Amt', 15, 2)->default(0.00)->after('visa_file');
        });

        Schema::table('visa_renewal_children', function (Blueprint $table) {
           $table->dropColumn('Amt', 15, 2)->default(0.00)->after('visa_file');
        });

    }
}
