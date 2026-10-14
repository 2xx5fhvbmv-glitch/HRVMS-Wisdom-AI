<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wisdom AI usage limits: one row per chat message that reached the AI
 * provider (successful rows are the per-user daily count; cost sums are the
 * per-resort monthly budget), plus per-resort overrides of the config
 * defaults (services.wisdom_ai.*), editable by super-admin on the resort form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wisdom_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->unsignedBigInteger('user_id'); // resort_admins.id
            $table->string('tier', 20);
            $table->boolean('ok')->default(false);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->decimal('cost', 12, 6)->default(0); // USD, as reported by OpenRouter
            $table->unsignedSmallInteger('tool_calls')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['resort_id', 'created_at']);
        });

        Schema::table('resorts', function (Blueprint $table) {
            $table->unsignedInteger('wisdom_daily_limit')->nullable()->after('no_of_users');
            $table->decimal('wisdom_monthly_budget', 10, 2)->nullable()->after('wisdom_daily_limit');
        });
    }

    public function down(): void
    {
        Schema::table('resorts', function (Blueprint $table) {
            $table->dropColumn(['wisdom_daily_limit', 'wisdom_monthly_budget']);
        });
        Schema::dropIfExists('wisdom_usage');
    }
};
