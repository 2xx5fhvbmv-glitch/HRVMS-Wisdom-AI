<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Every product's qr_code used to be generated client-side, in the browser,
 * before the product even existed in the database — it encoded a plain
 * "name - price" display string, never the product's own id. Scanning it
 * from the employee mobile app therefore had no product id to look up via
 * GET shop/product-details/{id}, which is the actual root cause of "QR scan
 * freezes instead of showing product details" (nothing to navigate to).
 *
 * Product creation/edit (Shopkeeper\ConfigurationController) now generates
 * the QR server-side, encoding base64_encode((string) $product->id) — this
 * backfills that same real QR onto every product created before that fix.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('products')->orderBy('id')->chunk(200, function ($products) {
            foreach ($products as $product) {
                $qr = QrCode::format('svg')->size(256)->generate(base64_encode((string) $product->id));
                DB::table('products')->where('id', $product->id)->update(['qr_code' => $qr]);
            }
        });
    }

    public function down()
    {
        // Original QR content (name/price label) carried no useful
        // information and can't be reconstructed reliably — not reversible.
    }
};
