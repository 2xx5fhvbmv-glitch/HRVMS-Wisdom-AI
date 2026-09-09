<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // sos_history.mass_instructions is a single overwritable string
        // column — every Send Mass Instructions submission wiped out the
        // previous message with no way to see what was sent before. This
        // table replaces it as the write target going forward (the old
        // column is left in place, untouched, for backward compat).
        Schema::create('sos_mass_instructions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->unsignedBigInteger('sos_history_id');
            $table->text('message');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['sos_history_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sos_mass_instructions');
    }
};
