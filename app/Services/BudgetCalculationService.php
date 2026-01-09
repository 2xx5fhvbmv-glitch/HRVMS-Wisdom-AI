<?php
namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PositionMonthlyData;
use App\Models\ResortBudgetCost;
use App\Models\ResortBenefitGrid;
use App\Models\ManningandbudgetingConfigfiles;
use App\Models\ResortBenifitGrid;
use App\Models\Employee;
use App\Models\ResortPosition;

class BudgetCalculationService
{

    /**
     * Calculate monthly and annual budget for a specific department.
     *
     * @param int $departmentId
     * @param int $year
     * @param int $resortId
     * @return array
     */
    public function calculateBudgetForDepartment($employee): float
    {
        $resortId = Auth::guard('resort-admin')->user()->resort_id; // Authenticated resort ID

        // 1. Get the expatriate-local ratio for the resort
        $resortConfig = ManningandbudgetingConfigfiles::where('resort_id', $resortId)->first();
        if (!$resortConfig) {
            throw new \Exception('Resort configuration not found');
        }

        $xpatLocalRatio = [
            'xpat' => (int)$resortConfig->xpat,
            'local' => (int)$resortConfig->local
        ];

        // 2. Initialize total costs
        $totalMonthlyBudget = 0;

        $rank = $employee->rank;
        $basic_salary = $employee->basic_salary;

        if($employee->nationality == "Maldivian")
            $operationalCost = $this->calculateOperationalCosts('local', $rank,$basic_salary);
        else
            $operationalCost = $this->calculateOperationalCosts('xpat', $rank,$basic_salary);

        $totalMonthlyBudget += $operationalCost;
        return $totalMonthlyBudget;
        // dd($totalMonthlyBudget); 
        // return [
        //     'monthly_budget' => number_format((float)$totalMonthlyBudget, 2, '.', ''), // Ensure 2 decimal places
        //     'annual_budget' => number_format((float)$totalAnnualBudget, 2, '.', '')   // Ensure 2 decimal places
        // ];     
    }
    public function calculateBudgetForDepartment1(int $departmentId, int $positionMonthId): array
    {
        // $totalCost = 0;
        // dd($departmentId,$positionMonthId);
        $resortId = Auth::guard('resort-admin')->user()->resort_id; // Authenticated resort ID

        // 1. Get the expatriate-local ratio for the resort
        $resortConfig = ManningandbudgetingConfigfiles::where('resort_id', $resortId)->first();
        if (!$resortConfig) {
            throw new \Exception('Resort configuration not found');
        }

        // Assuming the expatriate-local ratio is stored as a string like "10:25"
        // list($xpatRatio, $localRatio) = explode(':', $resortConfig->xpat);


        // Convert the ratios to integers
        $xpatLocalRatio = [
            'xpat' => (int)$resortConfig->xpat,
            'local' => (int)$resortConfig->local
        ];

        // 2. Initialize total costs
        $totalMonthlyBudget = 0;

        // 3. Get all positions' monthly data for the specified year
        $monthlyData = PositionMonthlyData::where('id', $positionMonthId)->get();

        // 4. Loop through each month's data and calculate costs for each month
        foreach ($monthlyData as $data) {
            $positionId = $data->position_id;
            $headcount = $data->headcount;
            $vacantcount = $data->vacantcount; // Total vacant positions

            // 5. Get employee type counts for the position
            $xpatEmps = Employee::where('Position_id', $positionId)
                ->where('resort_id', $resortId)
                ->where('nationality', '!=', 'Maldivian')
                ->get();
            $xpatCount = count($xpatEmps);

            $localEmps = Employee::where('Position_id', $positionId)
                ->where('resort_id', $resortId)
                ->where('nationality', 'Maldivian')
                ->get();
            $localCount = count($localEmps);

            // 6. Calculate total employees (filled + vacant)
            $totalEmployees = $xpatCount + $localCount;

            // 7. Calculate expected expatriate and local counts based on the ratio
            $expectedXpatCount = ($xpatLocalRatio['xpat'] / array_sum($xpatLocalRatio)) * $totalEmployees;
            $expectedLocalCount = ($xpatLocalRatio['local'] / array_sum($xpatLocalRatio)) * $totalEmployees;

            // 8. Calculate vacant positions for expatriates and locals
            $vacantXpatCount = min($vacantcount, max(0, $expectedXpatCount - $xpatCount));
            $vacantLocalCount = min($vacantcount - $vacantXpatCount, max(0, $expectedLocalCount - $localCount));

            // 9. Calculate recruitment costs (applies only to vacant positions)
            $recruitmentCostForXpat = $this->calculateRecruitmentCosts('xpat', $vacantXpatCount);
            $recruitmentCostForLocal = $this->calculateRecruitmentCosts('local', $vacantLocalCount);

            // 10. Calculate operational costs (applies to both filled and vacant positions)
            $operationalCostForXpat = $this->calculateOperationalCosts('xpat', $xpatCount + $vacantXpatCount, $positionId);
            $operationalCostForLocal = $this->calculateOperationalCosts('local', $localCount + $vacantLocalCount, $positionId);

            // 11. Total recruitment and operational costs
            $totalMonthlyBudget += $recruitmentCostForXpat + $recruitmentCostForLocal + $operationalCostForXpat + $operationalCostForLocal;

            // Optionally: Add benefit costs based on benefit grid
            // $benefitCost = $this->calculateBenefitCosts($positionId, $data->position->employee_rank);
            // $totalMonthlyBudget += $benefitCost;
        }
        // dd($totalMonthlyBudget);
        // 12. Calculate total annual budget
        $totalAnnualBudget = $totalMonthlyBudget * 12;

        return [
            'monthly_budget' => number_format((float)$totalMonthlyBudget, 2, '.', ''), // Ensure 2 decimal places
            'annual_budget' => number_format((float)$totalAnnualBudget, 2, '.', '')   // Ensure 2 decimal places
        ];
    }

    /**
     * Calculate operational costs based on the position's frequency and headcount.
     *
     * @param string $employeeType
     * @param int $headcount
     * @return float
     */
    protected function calculateOperationalCosts(string $employeeType, int $rank, float $basic_salary): float
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        

        $Rank = config( 'settings.Position_Rank');
        $rank = array_key_exists($rank, $Rank) ? $Rank[$rank] : '';
        $basic_salary = $basic_salary;
        $costs = ResortBudgetCost::where('resort_id', $resort_id)
            ->where('status', 'active')
            ->where('cost_title', 'Operational Cost')
            ->where(function ($query) use ($employeeType) {
                if ($employeeType === 'xpat') {
                    $query->where('details', 'Xpat Only')
                            ->orWhere('details', 'Both');
                } elseif ($employeeType === 'local') {
                    $query->where('details', 'Locals Only')
                            ->orWhere('details', 'Both');
                }
            })
            ->get();

        return $this->calculateCosts($costs, $headcount=1,$basic_salary);
    }

    protected function calculateOperationalCosts1(string $employeeType, int $headcount, $positionId): float
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $positionRank = ResortPosition::where('id',$positionId)->first('rank');

        $Rank = config( 'settings.Position_Rank');
        $rank = array_key_exists($positionRank->rank, $Rank) ? $Rank[$positionRank->rank] : '';
        // dd($rank);
        // $benefitGrid = ResortBenifitGrid::where('resort_id', $resort_id)
        //     ->where('rank',$rank)
        //     ->first();
        // dd($benefitGrid);
        // if ($benefitGrid) {
        //     // Get all attributes of the benefitGrid as an array
        //     $attributes = $benefitGrid->getAttributes();

        //     // Loop through each attribute and value
        //     foreach ($attributes as $attribute => $value) {
        //         switch ($key) {
        //             case 'service_charge':
        //                 // Example: Fetch cost from another table based on emp_grade
        //                 $totalCost += $value * $headcount;
        //                 break;
        //             case 'ramadan_bonus':
        //                 // Example: Add service charge value to the total cost
        //                 $ramadan_bonus = ResortBudgetCost::where('particulars', 'Ramadan Bonus')
        //                 ->where('resort_id',$resort_id);
        //                 $totalCost += $ramadan_bonus * $headcount;
        //                 break;

        //             default:
        //                 // If no special cost calculation, you can log or skip the attribute
        //                 break;
        //         }
        //     }
        // }

        $costs = ResortBudgetCost::where('resort_id', $resort_id)
            ->where('status', 'active')
            ->where('cost_title', 'Operational Cost')
            ->where(function ($query) use ($employeeType) {
                if ($employeeType === 'xpat') {
                    $query->where('details', 'Xpat Only')
                            ->orWhere('details', 'Both');
                } elseif ($employeeType === 'local') {
                    $query->where('details', 'Locals Only')
                            ->orWhere('details', 'Both');
                }
            })
            ->get();

        return $this->calculateCosts($costs, $headcount);
    }

    /**
     * Calculate recruitment costs based on the employee type and headcount.
     *
     * @param string $employeeType
     * @param int $headcount
     * @return float
     */
    protected function calculateRecruitmentCosts(string $employeeType, int $headcount): float
    {
        // Fetch relevant recruitment costs from the ResortBudgetCost table
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $costs = ResortBudgetCost::where('resort_id', $resort_id)
            ->where('status', 'active')
            ->where('cost_title', 'Recruitment Cost')
            ->where(function ($query) use ($employeeType) {
                if ($employeeType === 'xpat') {
                    $query->where('details', 'Xpat Only')
                          ->orWhere('details', 'Both');
                } elseif ($employeeType === 'local') {
                    $query->where('details', 'Locals Only')
                          ->orWhere('details', 'Both');
                }
            })
            ->get();

        return $this->calculateCosts($costs, $headcount);
    }

    /**
     * Calculate costs based on frequency and headcount.
     *
     * @param \Illuminate\Support\Collection $costs
     * @param int $headcount
     * @return float
     */
    protected function calculateCosts($costs, int $headcount, float $basic_salary): float
    {
        $totalCost = 0;
        $resortId = Auth::guard('resort-admin')->user()->resort_id;

        foreach ($costs as $c) {
            $frequency = ucfirst(strtolower($c->frequency)); // Capitalize first letter and make the rest lowercase

            // Check if amount is numeric or percentage
            $amount = $c->amount; // Amount could be a numeric value or percentage
            $unit = $c->amount_unit; // Unit is either '$' or '%'

            // Retrieve basic salary if needed
            $basic_salary = $basic_salary;
            
            // Switch case to handle cost frequency
            switch ($frequency) {
                case 'Monthly':
                    if ($unit == '%') {
                        if ($basic_salary) {
                            // Calculate percentage of basic salary
                            $totalCost += (($basic_salary * $amount) / 100) * $headcount;
                        }
                    } else {
                        // Direct calculation for numeric values
                        $totalCost += ($amount) * $headcount;
                    }
                    break;

                case 'Yearly':
                    if ($unit == '%') {
                        if ($basic_salary) {
                            // Calculate percentage of basic salary yearly, then divide by 12 for monthly equivalent
                            $totalCost += ((($basic_salary * $amount) / 100) / 12) * $headcount;
                        }
                    } else {
                        $totalCost += ($amount / 12) * $headcount;
                    }
                    break;

                case 'Quarterly':
                    if ($unit == '%') {
                        if ($basic_salary) {
                            $totalCost += ((($basic_salary * $amount) / 100) / 3) * $headcount;
                        }
                    } else {
                        $totalCost += ($amount / 3) * $headcount;
                    }
                    break;

                case 'Daily':
                    if ($unit == '%') {
                        if ($basic_salary) {
                            // Assuming daily cost is calculated as 1/30th of the monthly cost
                            $totalCost += (($basic_salary * $amount) / 100 / 30) * $headcount;
                        }
                    } else {
                        $totalCost += ($amount * 30) * $headcount;
                    }
                    break;

                case 'One-time':
                    if ($unit == '%') {
                        if ($basic_salary) {
                            // One-time cost as a percentage of basic salary
                            $totalCost += ($basic_salary * $amount) / 100;
                        }
                    } else {
                        // One-time costs are generally fixed and do not depend on headcount
                        $totalCost += $amount;
                    }
                    break;

                case 'Hourly':
                    if ($unit == '%') {
                        if ($basic_salary) {
                            // Assuming hourly rate calculation formula based on percentage of basic salary
                            $totalCost += ($basic_salary * 12 / 365 / 8) * ($amount / 100);
                        }
                    } else if ($amount == "1.25 time of basic salary") {
                        if ($basic_salary) {
                            $totalCost += ($basic_salary * 12 / 365 / 8 * 1.25);
                        }
                    } else if ($amount == "1.5 times of basic salary") {
                        if ($basic_salary) {
                            $totalCost += ($basic_salary * 12 / 365 / 8 * 1.5);
                        }
                    } else {
                        $totalCost += $amount;
                    }
                    break;

                default:
                    break;
            }
        }

        return $totalCost;
    }


    /**
     * Calculate benefit costs from the benefit grid for a position.
     *
     * @param int $positionId
     * @param string $employeeRank
     * @return float
     */
    // protected function calculateBenefitCosts(int $positionId, string $employeeRank): float
    // {
    //     // Fetch the relevant benefit costs from the benefit grid

    //     $totalBenefitCost = 0;

    //     foreach ($benefitGrid as $benefit) {
    //         switch ($benefit->frequency) {
    //             case 'monthly':
    //                 // Monthly benefit cost
    //                 $totalBenefitCost += $benefit->amount;
    //                 break;

    //             case 'yearly':
    //                 // Yearly benefit cost, spread over 12 months
    //                 $totalBenefitCost += ($benefit->amount / 12);
    //                 break;

    //             case 'quarterly':
    //                 // Quarterly benefit cost, spread over 3 months
    //                 $totalBenefitCost += ($benefit->amount / 3);
    //                 break;

    //             case 'daily':
    //                 // Daily benefit cost, assume 30 days in a month for calculation
    //                 $totalBenefitCost += ($benefit->amount * 30);
    //                 break;

    //             case 'one-time':
    //                 // One-time benefit cost
    //                 $totalBenefitCost += $benefit->amount;
    //                 break;

    //             default:
    //                 break;
    //         }
    //     }

    //     return $totalBenefitCost;
    // }
}
?>
