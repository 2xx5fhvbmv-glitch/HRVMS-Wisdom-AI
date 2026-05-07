<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Assign To" on the disciplinary create form is no longer required, so
 * Committee_id needs to accept NULL on insert. Requires doctrine/dbal for
 * the column change.
 */
return new class extends Migration {
    public function up()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
            $table->unsignedBigInteger('Committee_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
            $table->unsignedBigInteger('Committee_id')->nullable(false)->change();
        });
    }
};
