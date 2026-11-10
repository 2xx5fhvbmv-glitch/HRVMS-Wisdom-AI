<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit run-3 M3: deleting a grievance case keeps it (and its investigation / witness records), marked deleted. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('grivance_submission_models', 'deleted_at')) {
            Schema::table('grivance_submission_models', fn (Blueprint $t) => $t->softDeletes());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grivance_submission_models', 'deleted_at')) {
            Schema::table('grivance_submission_models', fn (Blueprint $t) => $t->dropSoftDeletes());
        }
    }
};
