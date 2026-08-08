<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * status enum on grivance_submission_witnesses was ['Requested','Approved',
 * 'NoAction'] — no value meaning "the witness has actually submitted their
 * statement" (Approved is an HR/committee action, not the witness's own
 * submission). Needed for the new mobile witness-statement-submit endpoint
 * to mark a row as done.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE grivance_submission_witnesses MODIFY status ENUM('Requested','Approved','NoAction','Submitted') DEFAULT 'NoAction'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE grivance_submission_witnesses MODIFY status ENUM('Requested','Approved','NoAction') DEFAULT 'NoAction'");
    }
};
