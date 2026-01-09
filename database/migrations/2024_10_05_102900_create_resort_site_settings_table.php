<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortSiteSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_site_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('resort_id');
            $table->enum('currency',['MVR','Dollar'])->default('MVR');
            $table->string('header_img')->nullable();
            $table->string('footer_img')->nullable();
            $table->string('signature_img')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_site_settings');
    }
}
