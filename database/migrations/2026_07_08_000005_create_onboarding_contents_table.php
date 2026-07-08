<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic per-resort content blobs for onboarding sub-screens that were
 * pure static Lorem Ipsum on mobile (Harassment Prevention policy text,
 * Access/badge details). Mirrors the existing TermsAndCondition pattern
 * (one editable HTML blob per resort) but keyed by content_type so one
 * table covers both screens instead of adding a dedicated table per screen.
 * Acknowledging the Harassment Prevention content already works via the
 * existing generic on-boarding/store-acknowledgement endpoint
 * (acknowledgement_type is a free-text string) — no changes needed there.
 */
class CreateOnboardingContentsTable extends Migration
{
    public function up()
    {
        Schema::create('onboarding_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('content_type'); // 'harassment_prevention', 'access_details'
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();

            $table->unique(['resort_id', 'content_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding_contents');
    }
}
