<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One SMTP account per resort, used to send every module's outbound email
 * instead of the single shared .env account. resort_id is unsignedInteger
 * to match resorts.id (Schema::increments in create_resorts_table.php).
 * No row for a resort = keep using the system default (opt-in, not a
 * breaking cutover).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('resort_smtp_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->unique();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->string('host');
            $table->unsignedInteger('port')->default(587);
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('from_address');
            $table->string('from_name');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('resort_smtp_configs');
    }
};
