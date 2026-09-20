<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResortNonpermanentBudgetCostPosition extends Model
{
    protected $table = 'resort_nonpermanent_budget_cost_positions';
    protected $fillable = ['cost_id', 'position_id'];
    public $timestamps = true;
}
