<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs.file_id used to be NOT NULL with a default-RESTRICT FK to
 * child_file_management.id, which made it impossible to delete a file:
 * FileManageController@DeleteFile inserts an audit-log row pointing at
 * the file, then tries to delete the file — and the just-created log
 * row blocks the delete with a 1451 integrity error.
 *
 * Make file_id nullable and switch the FK to ON DELETE SET NULL so the
 * historical audit row survives the file's deletion (file_path and
 * uploaded_by columns already carry the human-readable trail).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign('audit_logs_file_id_foreign');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('file_id')->nullable()->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('file_id')
                ->references('id')
                ->on('child_file_management')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign('audit_logs_file_id_foreign');
        });

        // Cannot safely revert file_id to NOT NULL if any rows now hold
        // NULL after a file deletion — leave the column nullable and
        // restore the original RESTRICT FK behaviour.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('file_id')
                ->references('id')
                ->on('child_file_management');
        });
    }
};
