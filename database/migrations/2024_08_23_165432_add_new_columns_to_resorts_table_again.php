<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToResortsTableAgain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resorts', function (Blueprint $table) {
            $table->text('headoffice_address1')->nullable();
            $table->text('headoffice_address2')->nullable();
            $table->string('headoffice_city')->nullable();
            $table->string('headoffice_state')->nullable();
            $table->string('headoffice_country')->nullable();
            $table->string('headoffice_pincode')->nullable();
            $table->string('support_preference')->nullable();
            $table->string('Support_SLA')->nullable();
            
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
            $table->dropColumn('headoffice_address1');
            $table->dropColumn('headoffice_address2');
            $table->dropColumn('headoffice_city');
            $table->dropColumn('headoffice_state');
            $table->dropColumn('headoffice_country');
            $table->dropColumn('headoffice_pincode');
            $table->dropColumn('support_preference');
            $table->dropColumn('Support_SLA');
        });
    }
}
