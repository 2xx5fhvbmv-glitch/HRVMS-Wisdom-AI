<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 of the File & Folder Share feature (WA-DEV-2026-SHARE-01).
 *
 * Three tables:
 *   file_shares             — one row per share record (polymorphic across
 *                             files and folders, internal or external)
 *   file_share_employees    — recipient employees for internal "specific
 *                             employees" shares
 *   file_share_departments  — recipient departments for internal "departments" shares
 *
 * "Entire organization" shares are stored on file_shares alone with
 * scope_type='organization' and no rows in the junction tables — the
 * lookup short-circuits to "all employees of the sharer's resort".
 *
 * "External" rows are kept in the same file_shares table with mode='external'
 * + token + expires_at; phase 2 will wire the public resolver route.
 *
 * Cascade on share deletion takes the two junctions with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            // Polymorphic — 'file' = child_file_management row, 'folder' = filemangement_systems row
            $table->enum('shareable_type', ['file', 'folder']);
            $table->unsignedBigInteger('shareable_id');
            // 'internal' = visible within the org (employees/depts/everyone)
            // 'external' = anonymous token-based URL (phase 2 wires it up)
            $table->enum('share_mode', ['internal', 'external']);
            // Only set for internal shares. Null for external.
            $table->enum('scope_type', ['employees', 'departments', 'organization'])->nullable();
            // The owner — resort_admins.id (sharer)
            $table->unsignedBigInteger('shared_by');
            // Resort the share originates from. Even for cross-resort
            // employee shares we record the originating resort so it's
            // possible to filter "shares I sent from resort X".
            $table->unsignedInteger('resort_id');
            // External token + expiry (null for internal)
            $table->string('token', 64)->nullable();
            $table->dateTime('expires_at')->nullable();
            // JSON permissions blob for forward-compatibility ({view:true, download:true, ...})
            // Phase 1 always writes {"view": true}.
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->index(['shareable_type', 'shareable_id'], 'idx_shareable');
            $table->index('token');
            $table->index('shared_by');
            $table->index('resort_id');
        });

        Schema::create('file_share_employees', function (Blueprint $table) {
            $table->unsignedBigInteger('share_id');
            // employees.id (NOT resort_admins.id) — recipient employee
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['share_id', 'employee_id'], 'pk_fs_employees');
            $table->index('employee_id', 'idx_fs_emp_recipient');
            $table->foreign('share_id', 'fk_fs_emp_share')
                ->references('id')->on('file_shares')
                ->onDelete('cascade');
        });

        Schema::create('file_share_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('share_id');
            // resort_departments.id — every active employee in this dept
            // (at the time of access, resolved live) gets read access.
            $table->unsignedBigInteger('department_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['share_id', 'department_id'], 'pk_fs_departments');
            $table->index('department_id', 'idx_fs_dept_recipient');
            $table->foreign('share_id', 'fk_fs_dept_share')
                ->references('id')->on('file_shares')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_share_departments');
        Schema::dropIfExists('file_share_employees');
        Schema::dropIfExists('file_shares');
    }
};
