<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scopes a resort_positions row to Casual/Intern (or leaves it Permanent).
 * NULL = Permanent — every existing row today, zero backfill needed. Only
 * rows created through the new Casual/Intern Position Configuration screen
 * get 'Casual' or 'Intern' here.
 *
 * code/short_title made nullable in the same pass: the config screen only
 * captures a free-text position title for Casual/Intern (no rank/grade/code
 * applies to them), and both columns were non-nullable with no default.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            $table->enum('employee_category', ['Casual', 'Intern'])->nullable()->after('dept_id');
        });

        Schema::table('resort_positions', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
            $table->string('short_title')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            $table->dropColumn('employee_category');
        });
    }
};
