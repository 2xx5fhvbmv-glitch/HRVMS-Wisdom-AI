<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ta_email_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('Resort_id');
            $table->string('TempleteName')->nullable();
            $table->text('MailTemplete')->nullable();
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ta_email_templates');
    }
}
