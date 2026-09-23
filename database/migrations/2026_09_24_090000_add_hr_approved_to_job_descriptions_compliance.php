<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE job_descriptions MODIFY compliance ENUM('Approved','HR Approved','Rejected') NOT NULL DEFAULT 'Rejected'");
    }

    public function down()
    {
        DB::table('job_descriptions')->where('compliance', 'HR Approved')->update(['compliance' => 'Approved']);
        DB::statement("ALTER TABLE job_descriptions MODIFY compliance ENUM('Approved','Rejected') NOT NULL DEFAULT 'Rejected'");
    }
};
