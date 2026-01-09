<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManifestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifest', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->enum('manifest_type', ['arrival', 'departure']);
            $table->string('transportation_mode');
            $table->string('transportation_name');
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['draft', 'confirmed', 'saved', 'closed'])->default('saved');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');           
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manifest');
    }
}
