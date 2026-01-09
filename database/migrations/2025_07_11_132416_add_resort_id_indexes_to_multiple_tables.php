<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResortIdIndexesToMultipleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'resort_interal_pages_permissions',
            'resort_pagewise_permissions',
            'payroll_employees',
            'resort_admins',
            'employees',
            'resort_budget_costs',
            'resort_benifit_grid',
            'resort_earnings',
            'resort_site_settings',
            'resort_deductions',
            'resort_roles',
            'occuplanies',
            'resort_module_permissions',
        ];

       foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'resort_id')) {
                Schema::table($table, function (Blueprint $tbl) use ($table) {
                    $indexName = $table . '_resort_id_index';
                    $tbl->index('resort_id', $indexName);
                });
            }
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = [
            'resort_interal_pages_permissions',
            'resort_pagewise_permissions',
            'payroll_employees',
            'resort_admins',
            'employees',
            'resort_budget_costs',
            'resort_benifit_grid',
            'resort_earnings',
            'resort_site_settings',
            'resort_deductions',
            'resort_roles',
            'occuplanies',
            'resort_module_permissions',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'resort_id')) {
                Schema::table($table, function (Blueprint $tbl) use ($table) {
                    $indexName = $table . '_resort_id_index';
                    $tbl->dropIndex($indexName);
                });
            }
        }
    }
}
