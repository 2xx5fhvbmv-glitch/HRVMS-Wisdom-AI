<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('performa_child_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('performa_child_cycles', 'template_id')) {
                $table->string('template_id', 50)->nullable()->after('Manager_id');
            }
            if (!Schema::hasColumn('performa_child_cycles', 'is_gm_review')) {
                $table->boolean('is_gm_review')->default(false)->after('template_id');
            }
            if (!Schema::hasColumn('performa_child_cycles', 'self_review_data')) {
                $table->longText('self_review_data')->nullable()->after('is_gm_review');
            }
            if (!Schema::hasColumn('performa_child_cycles', 'manager_review_data')) {
                $table->longText('manager_review_data')->nullable()->after('self_review_data');
            }
            if (!Schema::hasColumn('performa_child_cycles', 'self_review_status')) {
                $table->enum('self_review_status', ['pending', 'completed'])->default('pending')->after('manager_review_data');
            }
            if (!Schema::hasColumn('performa_child_cycles', 'manager_review_status')) {
                $table->enum('manager_review_status', ['pending', 'completed', 'not_applicable'])->default('pending')->after('self_review_status');
            }
            if (!Schema::hasColumn('performa_child_cycles', 'exported_at')) {
                $table->timestamp('exported_at')->nullable()->after('manager_review_status');
            }
        });
    }

    public function down()
    {
        Schema::table('performa_child_cycles', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'is_gm_review', 'self_review_data', 'manager_review_data', 'self_review_status', 'manager_review_status', 'exported_at']);
        });
    }
};
