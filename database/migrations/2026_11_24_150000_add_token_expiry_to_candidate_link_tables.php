<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Public candidate links (offer, contract, interview, consent, availability) never expired.
 * Each token gets an expiry; links already out get 30 days from now so in-flight candidates
 * are not cut off (ApplicantOfferContract::TOKEN_TTL_DAYS).
 */
return new class extends Migration
{
    private const COLUMNS = [
        // table => [expiry column => token column]
        'applicant_offer_contracts' => ['token_expires_at' => 'token'],
        'applicant_inter_view_details' => ['token_expires_at' => 'invitation_token'],
        'applicant_form_data' => [
            'consent_token_expires_at' => 'consent_token',
            'availability_token_expires_at' => 'availability_token',
        ],
    ];

    public function up(): void
    {
        $expiry = now()->addDays(30);
        foreach (self::COLUMNS as $table => $cols) {
            foreach ($cols as $expiryCol => $tokenCol) {
                if (!Schema::hasColumn($table, $expiryCol)) {
                    Schema::table($table, function (Blueprint $t) use ($expiryCol, $tokenCol) {
                        $t->dateTime($expiryCol)->nullable()->after($tokenCol);
                    });
                }
                DB::table($table)->whereNotNull($tokenCol)->whereNull($expiryCol)->update([$expiryCol => $expiry]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $cols) {
            foreach (array_keys($cols) as $expiryCol) {
                if (Schema::hasColumn($table, $expiryCol)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropColumn($expiryCol));
                }
            }
        }
    }
};
