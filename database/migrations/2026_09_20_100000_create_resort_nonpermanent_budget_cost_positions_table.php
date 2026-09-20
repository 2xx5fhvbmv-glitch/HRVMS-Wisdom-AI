<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WP2 (D2) — ties a resort_nonpermanent_budget_costs line to specific
 * Casual/Intern positions. No rows for a cost = applies to every position
 * of that cost's own applies_to category (the existing, unrestricted
 * behavior) — this is purely additive scoping, never required.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('resort_nonpermanent_budget_cost_positions')) {
            return;
        }
        Schema::create('resort_nonpermanent_budget_cost_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cost_id');
            $table->unsignedInteger('position_id');
            $table->timestamps();

            $table->foreign('cost_id')->references('id')->on('resort_nonpermanent_budget_costs')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('resort_positions')->onDelete('cascade');
            $table->unique(['cost_id', 'position_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('resort_nonpermanent_budget_cost_positions');
    }
};
