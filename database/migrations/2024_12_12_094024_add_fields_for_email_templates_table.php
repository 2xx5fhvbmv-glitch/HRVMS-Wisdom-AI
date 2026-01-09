<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsForEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ta_email_templates', function (Blueprint $table) {
            $table->string('MailSubject')->nullable(); 
            $table->json('Placeholders')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ta_email_templates', function (Blueprint $table) {
            $table->dropColumn('MailSubject'); 
            $table->dropColumn('Placeholders'); 
        });
    }
}
