<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Prompt tokens served from the provider's cache (Claude prompt caching), to see the saving per resort.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wisdom_usage', function (Blueprint $table) {
            $table->unsignedInteger('cached_tokens')->default(0)->after('completion_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('wisdom_usage', function (Blueprint $table) {
            $table->dropColumn('cached_tokens');
        });
    }
};
