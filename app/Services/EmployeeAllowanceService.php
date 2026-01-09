<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenefitGridChild;
use App\Models\ResortBudgetCost;
use Illuminate\Support\Str;

class EmployeeAllowanceService
{
    public function generateAllowancesForEmployee(Employee $employee)
    {
        // STEP 1: Match benefit grid
        $benefitGrid = ResortBenifitGrid::where('resort_id', $employee->resort_id)
            ->where('emp_grade', $employee->grade)
            ->where('rank', $employee->rank)
            ->where('contract_status', $employee->contract_status)
            ->latest('effective_date')
            ->first();

        if (!$benefitGrid) {
            return false; // or throw exception
        }

        // STEP 2: Fixed/Boolean Benefits from benefit grid
        $allowanceFields = [
            'ramadan_bonus', 'uniform', 'health_care_insurance', 'internet_access',
            'meals_per_day', 'accommodation_status', 'laundry', 'annual_leave_ticket',
            'telephone', 'spa_discount', 'food_and_beverages_discount', 'rest_and_relaxation_allowance',
            'relocation_ticket', 'furniture_and_fixtures'
        ];

        foreach ($allowanceFields as $field) {
            $value = $benefitGrid->$field;

            if (!empty($value)) {
                EmployeeAllowance::create([
                    'employee_id' => $employee->id,
                    'allowance_key' => $field,
                    'allowance_type' => is_numeric($value) ? 'fixed' : 'in-kind',
                    'amount' => is_numeric($value) ? $value : null,
                    'frequency' => 'monthly', // default assumption
                    'is_taxable' => false,
                    'is_in_service_charge' => false,
                    'source' => 'benefit_grid',
                    'benefit_grid_id' => $benefitGrid->id
                ]);
            }
        }

        // STEP 3: Leave-based benefits
        $leaveBenefits = ResortBenefitGridChild::where('benefit_grid_id', $benefitGrid->id)->get();

        foreach ($leaveBenefits as $leave) {
            EmployeeAllowance::create([
                'employee_id' => $employee->id,
                'allowance_key' => 'leave_' . $leave->leave_cat_id,
                'allowance_type' => 'leave',
                'amount' => $leave->allocated_days,
                'frequency' => 'yearly',
                'is_taxable' => false,
                'is_in_service_charge' => false,
                'source' => 'benefit_grid_child',
                'benefit_grid_id' => $benefitGrid->id
            ]);
        }

        // STEP 4: Cost-based allowances from budget
        $budgetCosts = ResortBudgetCost::where('resort_id', $employee->resort_id)
            ->where('cost_type', 'operational_cost')
            ->where('status', 1)
            ->get();

        $employeeSpecificParticulars = [
            'Meal Allowance',
            'Internet Access',
            'Telephone Allowance',
        ];

        foreach ($budgetCosts as $cost) {
            if (in_array($cost->particulars, $employeeSpecificParticulars)) {
                EmployeeAllowance::create([
                    'employee_id' => $employee->id,
                    'allowance_key' => Str::slug($cost->particulars, '_'),
                    'allowance_type' => 'fixed',
                    'amount' => $cost->amount,
                    'frequency' => $cost->frequency,
                    'is_taxable' => false,
                    'is_in_service_charge' => false,
                    'source' => 'budget_cost',
                ]);
            }
        }

        return true;
    }
}
