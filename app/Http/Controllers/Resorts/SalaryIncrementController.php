<?php
namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryIncrement;
use App\Models\StoreManningResponseChild;
use App\Models\StoreManningResponseParent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Common;
use App\Http\Requests\StoreSalaryIncrementRequest;

class SalaryIncrementController extends Controller
{
    public function getIncrementDetails(Request $request)
    {
        try {
            $employee_id = $request->employee_id;

            $lastIncrement = SalaryIncrement::where('employee_id', $employee_id)
                ->latest()
                ->first();


            return response()->json([
                'success' => true,
                'last_increment' => $lastIncrement ?? [
                    'effective_date' => null,
                    'increment_amount' => 0,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching increment details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching increment details'
            ], 500);
        }
    }

    public function saveSalaryIncrement(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'previous_salary' => 'required|numeric|min:0',
                'new_salary' => 'required|numeric|min:0',
                'increment_amount' => 'required|numeric|min:0',
                'increment_percentage' => 'required|numeric',
                'effective_date' => 'required|date',
            ]);

            // Create new increment record
            $increment = SalaryIncrement::create([
                'employee_id' => $validated['employee_id'],
                'previous_salary' => $validated['previous_salary'],
                'new_salary' => $validated['new_salary'],
                'increment_amount' => $validated['increment_amount'],
                'increment_percentage' => $validated['increment_percentage'] ?? 0,
                'reason' => $request->get('increment_reason', ""),
                'effective_date' => $validated['effective_date'],
                'notes' => $request->get('notes', "")
            ]);

            // Update employee's salary
            $employee = Employee::findOrFail($validated['employee_id']);
            $employee->update(
                [
                    'proposed_salary' => $validated['new_salary'],
                    'proposed_salary_unit'=>$validated['proposed_salary_unit'] ?? 'USD',
                    'incremented_date'=>$validated['effective_date']
                ]);

            // Fetch the relevant StoreManningResponseParent record
            $smrp = StoreManningResponseParent::where('Resort_id', $request->resortId)
                ->where('Department_id', $request->dept_id)
                ->where('Budget_id', $request->Budget_id)
                ->first(); // Use first() instead of get() to get a single record

            if ($smrp) {
                // Update StoreManningResponseChild with the new salary
                StoreManningResponseChild::updateOrCreate(
                    [
                        'Parent_SMRP_id' => $smrp->id,
                        'Emp_id' => $validated['employee_id'],
                    ],
                    [
                        'Parent_SMRP_id' => $smrp->id,
                        'Emp_id' => $validated['employee_id'],
                        'Proposed_Basic_salary' => $validated['new_salary'],
                    ]
                );
            } else {
                // Handle the case where there is no matching SMRP
                return response()->json([
                    'success' => false,
                    'message' => 'No matching Store Manning Response found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Salary increment saved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving increment details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving increment details'
            ], 500);
        }
    }

    public function saveBulkSalaryIncrement(Request $request)
    {
        // try {
            $validated = $request->validate([
                'resort_id' => 'required|exists:resorts,id',
                'dept_id' => 'required|exists:resort_departments,id',
                'budget_id' => 'required|exists:manning_responses,id',
                'increment_amount' => 'nullable|numeric|min:0',
                'increment_percentage' => 'nullable|numeric|min:0',
                'effective_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            // Ensure only one increment type is provided
            if ((!$request->increment_amount && !$request->increment_percentage) ||
                ($request->increment_amount && $request->increment_percentage)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide either increment amount or increment percentage.'
                ], 400);
            }

            // Fetch all employees in the specified department and resort
            $employees = Employee::where('resort_id', $validated['resort_id'])
                ->where('Dept_id', $validated['dept_id'])
                ->get();

            foreach ($employees as $employee) {
                // dd($employee);

                $previousSalary = $employee->basic_salary;

                // Calculate new salary based on increment type
                if ($request->increment_percentage) {
                    $incrementAmount = ($previousSalary * $validated['increment_percentage']) / 100;
                    $newSalary = $previousSalary + $incrementAmount;
                } else {
                    $incrementAmount = $validated['increment_amount'];
                    $newSalary = $previousSalary + $incrementAmount;
                }

                // Create increment record
                SalaryIncrement::create([
                    'employee_id' => $employee->id,
                    'previous_salary' => $previousSalary,
                    'new_salary' => $newSalary,
                    'increment_amount' => $incrementAmount,
                    'increment_percentage' => $request->increment_percentage ?? 0,
                    'effective_date' => $validated['effective_date'],
                    'reason' => $request->input('increment_reason', ''),
                    'notes' => $validated['notes'] ?? '',
                ]);

                // Update employee's basic salary
                $employee->proposed_salary = $newSalary;
                $employee->proposed_salary_unit = $request->input('proposed_salary_unit', 'USD');
                $employee->incremented_date = $validated['effective_date'];
                $employee->save();
                // Update StoreManningResponseChild
                $smrp = StoreManningResponseParent::where('Resort_id', $request->resort_id)
                    ->where('Department_id', $request->dept_id)
                    ->where('Budget_id', $request->budget_id)
                    ->first();

                if ($smrp) {
                    StoreManningResponseChild::updateOrCreate(
                        [
                            'Parent_SMRP_id' => $smrp->id,
                            'Emp_id' => $employee->id,
                        ],
                        [
                            'Proposed_Basic_salary' => $newSalary,
                        ]
                    );
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No matching Store Manning Response found.'
                    ], 404);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk salary increments applied successfully'
            ]);
        // } catch (\Exception $e) {
        //     \Log::error('Error applying bulk salary increments: ' . $e->getMessage());
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error applying bulk salary increments'
        //     ], 500);
        // }
    }
}
