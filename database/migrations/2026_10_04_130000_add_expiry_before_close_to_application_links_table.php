<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The link's expiry as it was when its vacancy was closed, so reopening restores it.
        Schema::table('application_links', function (Blueprint $table) {
            $table->date('expiry_before_close')->nullable()->after('link_Expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('application_links', function (Blueprint $table) {
            $table->dropColumn('expiry_before_close');
        });
    }
};
