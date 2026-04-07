<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssignmentTypeToInventoryModules extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('inventory_modules', 'assignment_type')) {
            Schema::table('inventory_modules', function (Blueprint $table) {
                $table->string('assignment_type')->default('per_person')->after('MinMumStockQty');
                $table->integer('default_qty_per_unit')->default(1)->after('assignment_type');
            });
        }

        if (!Schema::hasColumn('available_accommodation_inv_items', 'quantity')) {
            Schema::table('available_accommodation_inv_items', function (Blueprint $table) {
                $table->integer('quantity')->default(1)->after('Item_id');
            });
        }

        if (!Schema::hasColumn('maintanace_requests', 'start_time')) {
            Schema::table('maintanace_requests', function (Blueprint $table) {
                $table->time('start_time')->nullable()->after('date');
                $table->time('end_time')->nullable()->after('start_time');
            });
        }

        // Make item_id nullable for events without inventory
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->integer('item_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('inventory_modules', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'default_qty_per_unit']);
        });

        Schema::table('available_accommodation_inv_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
}
