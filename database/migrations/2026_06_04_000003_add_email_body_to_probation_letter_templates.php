<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Split letter templates into two surfaces:
 *   • `content`    — formal letter body rendered into the PDF attachment
 *   • `email_body` — short notification message used as the email body
 *
 * Both fields honour the same {{placeholder}} substitution. When
 * `email_body` is empty (legacy templates), the mailer falls back to a
 * generated "Please find your <Subject> attached" boilerplate so old
 * data doesn't break sending.
 *
 * Idempotent: skips when the column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('probation_letter_templates')) {
            return;
        }
        if (Schema::hasColumn('probation_letter_templates', 'email_body')) {
            return;
        }
        Schema::table('probation_letter_templates', function (Blueprint $table) {
            $table->longText('email_body')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('probation_letter_templates') || !Schema::hasColumn('probation_letter_templates', 'email_body')) {
            return;
        }
        Schema::table('probation_letter_templates', function (Blueprint $table) {
            $table->dropColumn('email_body');
        });
    }
};
