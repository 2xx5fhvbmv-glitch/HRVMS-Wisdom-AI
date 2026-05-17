<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 7 — Transfer Letter.
 *
 * Stores the relative storage path of the generated Transfer Letter PDF on
 * the transfer record so it can be re-downloaded / re-emailed without
 * regenerating, and so the listing can show whether the letter was issued.
 *
 * The `letter_dispatched` enum ('Yes'/'No') already exists on the table and
 * is flipped to 'Yes' once the letter is generated & emailed.
 */
class AddTransferLetterPathToEmployeeTransfers extends Migration
{
    public function up()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_transfers', 'transfer_letter_path')) {
                $table->string('transfer_letter_path')->nullable()->after('letter_dispatched');
            }
        });
    }

    public function down()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('employee_transfers', 'transfer_letter_path')) {
                $table->dropColumn('transfer_letter_path');
            }
        });
    }
}
