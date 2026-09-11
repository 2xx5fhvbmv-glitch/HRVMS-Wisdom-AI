<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortNonpermanentBudgetCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Mirrors resort_budget_costs' shape (same amount/amount_unit/
        // frequency/details vocabulary — reused, not reinvented) plus one
        // addition: applies_to, since Casual and Intern share this screen
        // but don't always share every line item. Deliberately NOT a
        // benefit_grid_levels-scoped extension of resort_budget_costs
        // itself — benefit grids are a Permanent-staff compensation-policy
        // construct Casual/Intern don't have.
        Schema::create('resort_nonpermanent_budget_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->string('cost_title');
            $table->string('particulars')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('amount_unit');
            $table->string('cost_type')->nullable();
            $table->string('frequency');
            $table->enum('applies_to', ['Casual', 'Intern', 'Both'])->default('Both');
            $table->string('details')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->index('resort_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_nonpermanent_budget_costs');
    }
}
