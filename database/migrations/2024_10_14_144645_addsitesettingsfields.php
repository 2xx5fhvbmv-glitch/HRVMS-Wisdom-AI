<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Addsitesettingsfields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_site_settings', function (Blueprint $table) {

            $table->double('MVRtoDoller')->after('currency');
            $table->double('DollertoMVR')->after('MVRtoDoller');
            $table->string('MVR_img')->after('DollertoMVR',100);
            $table->string('Doller_img')->after('MVR_img',100);
            $table->text('Footer')->after('Doller_img',100);

        });
        Schema::table('resort_admins', function (Blueprint $table) {


            $table->string('signature_img');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_site_settings', function (Blueprint $table) {

            $table->dropColumn('MVRtoDoller')->after('currency');
            $table->dropColumn('DollertoMVR')->after('MVRtoDoller');
            $table->dropColumn('MVR_img')->after('DollertoMVR',100);
            $table->dropColumn('Doller_img')->after('MVR_img',100);

            $table->dropColumn('Footer');
               });
        Schema::table('resort_admins', function (Blueprint $table) {

            $table->dropColumn('signature_img');


        });
    }
}
