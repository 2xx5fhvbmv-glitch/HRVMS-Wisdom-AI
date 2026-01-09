<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFieldsResortNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update resorts_parent_notifications table
        Schema::table('resorts_parent_notifications', function (Blueprint $table) {

            // Drop columns if they exist
            if (Schema::hasColumn('resorts_parent_notifications', 'Department_id')) {
                $table->dropColumn('Department_id');
            }

            if (Schema::hasColumn('resorts_parent_notifications', 'Position_id')) {
                $table->dropColumn('Position_id');
            }


            // Add columns if they do not exist
            if (!DB::getSchemaBuilder()->hasColumn('resorts_parent_notifications', 'created_by')) {
                $table->integer('created_by')->nullable();
            }

            if (!DB::getSchemaBuilder()->hasColumn('resorts_parent_notifications', 'modified_by')) {
                $table->integer('modified_by')->nullable();
            }
        });

        // Update resorts_child_notifications table
        Schema::table('resorts_child_notifications', function (Blueprint $table) {

            // Drop columns if they exist
            // if (Schema::hasColumn('resorts_child_notifications', 'Parent_msg_id')) {
            //     $table->dropColumn('Parent_msg_id');
            // }

            // if (Schema::hasColumn('resorts_child_notifications', 'Department_id')) {
            //     $table->dropColumn('Department_id');
            // }

            // if (Schema::hasColumn('resorts_child_notifications', 'Position_id')) {
            //     $table->dropColumn('Position_id');
            // }

            // if (Schema::hasColumn('resorts_child_notifications', 'created_by')) {
            //     $table->dropColumn('created_by');
            // }

            // if (Schema::hasColumn('resorts_child_notifications', 'modified_by')) {
            //     $table->dropColumn('modified_by');
            // }

            // Add columns if they do not exist
            if (!DB::getSchemaBuilder()->hasColumn('resorts_child_notifications', 'Parent_msg_id')) {
                $table->string('Parent_msg_id', 150)->nullable();
            }

            if (!DB::getSchemaBuilder()->hasColumn('resorts_child_notifications', 'Department_id')) {
                $table->string('Department_id', 150)->nullable();
            }

            if (!DB::getSchemaBuilder()->hasColumn('resorts_child_notifications', 'Position_id')) {
                $table->string('Position_id', 150)->nullable();
            }

            if (!DB::getSchemaBuilder()->hasColumn('resorts_child_notifications', 'created_by')) {
                $table->integer('created_by')->nullable();
            }

            if (!DB::getSchemaBuilder()->hasColumn('resorts_child_notifications', 'modified_by')) {
                $table->integer('modified_by')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert changes in resorts_child_notifications table
        Schema::table('resorts_child_notifications', function (Blueprint $table) {

            // Drop columns if they exist
            if (Schema::hasColumn('resorts_child_notifications', 'Parent_msg_id')) {
                $table->dropColumn('Parent_msg_id');
            }

            if (Schema::hasColumn('resorts_child_notifications', 'Department_id')) {
                $table->dropColumn('Department_id');
            }

            if (Schema::hasColumn('resorts_child_notifications', 'Position_id')) {
                $table->dropColumn('Position_id');
            }

            if (Schema::hasColumn('resorts_child_notifications', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('resorts_child_notifications', 'modified_by')) {
                $table->dropColumn('modified_by');
            }
        });

        // Revert changes in resorts_parent_notifications table
        Schema::table('resorts_parent_notifications', function (Blueprint $table) {

            // Drop columns if they exist
            if (Schema::hasColumn('resorts_parent_notifications', 'modified_by')) {
                $table->dropColumn('modified_by');
            }

            if (Schema::hasColumn('resorts_parent_notifications', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
}
