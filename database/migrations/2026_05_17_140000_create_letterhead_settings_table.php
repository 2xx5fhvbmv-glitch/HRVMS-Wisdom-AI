<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Letterhead & E-signature configuration.
 *
 * One row per resort. Holds the branded letterhead header image, an optional
 * footer image, the stored e-signature image, the signatory name + title and
 * the address / contact text printed on document/letter PDFs.
 *
 * This replaces the ad-hoc "resort logo + typed signature" approximation that
 * the Transfer Letter (and, later, Probation / Promotion letters) currently
 * uses. Image paths are stored relative to public/ (e.g.
 * "uploads/letterheads/{resort_id}/header_xxx.png").
 */
class CreateLetterheadSettingsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('letterhead_settings')) {
            return;
        }

        Schema::create('letterhead_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->unique();

            // Branded letterhead images (paths relative to public/).
            $table->string('header_image')->nullable();
            $table->string('footer_image')->nullable();
            $table->string('signature_image')->nullable();

            // Signatory block printed above the signature image.
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();

            // Address / contact text rendered on the letterhead.
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('contact_phone', 100)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('website', 150)->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    public function down()
    {
        Schema::dropIfExists('letterhead_settings');
    }
}
