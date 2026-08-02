<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "Salary Advance" People submenu also covers Loan Requests (same
 * page, request_type differentiates Salary Advance vs Loan Request) —
 * renamed to make that clear instead of only naming half of what the
 * page does.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('module_pages')
            ->where('internal_route', 'people.advance-salary.index')
            ->where('page_name', 'Salary Advance')
            ->update(['page_name' => 'Salary & Loan Advance']);
    }

    public function down()
    {
        DB::table('module_pages')
            ->where('internal_route', 'people.advance-salary.index')
            ->where('page_name', 'Salary & Loan Advance')
            ->update(['page_name' => 'Salary Advance']);
    }
};
