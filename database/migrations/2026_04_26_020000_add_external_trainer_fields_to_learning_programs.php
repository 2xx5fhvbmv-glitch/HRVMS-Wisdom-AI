<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds external-trainer metadata to learning_programs:
 *  - external_training         : free-text name of the external program
 *  - external_trainer_company  : company / vendor name
 *  - trainer_image             : relative path on the local disk to an uploaded image
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_programs')) return;

        Schema::table('learning_programs', function (Blueprint $t) {
            if (!Schema::hasColumn('learning_programs', 'external_training')) {
                $t->string('external_training', 255)->nullable()->after('trainer');
            }
            if (!Schema::hasColumn('learning_programs', 'external_trainer_company')) {
                $t->string('external_trainer_company', 255)->nullable()->after('external_training');
            }
            if (!Schema::hasColumn('learning_programs', 'trainer_image')) {
                $t->string('trainer_image', 500)->nullable()->after('external_trainer_company');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('learning_programs')) return;

        Schema::table('learning_programs', function (Blueprint $t) {
            foreach (['external_training', 'external_trainer_company', 'trainer_image'] as $col) {
                if (Schema::hasColumn('learning_programs', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
