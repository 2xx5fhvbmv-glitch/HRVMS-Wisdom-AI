<?php

namespace App\Http\Controllers\Resorts\Payroll;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Earnings;
use App\Models\Deduction;
use App\Models\PayrollConfig;
use App\Models\PublicHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ParentAttendace;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\Payment;
use App\Models\ResortSiteSettings;
use App\Models\Payroll;
use App\Models\PayrollDeduction;
use App\Models\PayrollEmployees;
use App\Models\PayrollReview;
use App\Models\PayrollServiceCharge;
use App\Models\PayrollTimeAndAttendance;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;
use Common;

class EWTController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        $page_title ='Employee Withholding Tax (EWT)';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->where('status', 'Active')->get();
          $deductions = Deduction::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        return view('resorts.payroll.EWT.index',compact('page_title','positions','departments','deductions','employees'));
    }

    // public function getEWTData(Request $request, $year)
    // {
    //     try {
    //         $resort_id = $this->resort->resort_id;
    //         $sitesettings = ResortSiteSettings::where('resort_id', $resort_id)->first();
    //         $conversionRate = $sitesettings['DollertoMVR'] ?? 15.42;

    //         // Initialize all months
    //         $months = collect(range(1, 12))->mapWithKeys(fn($m) => [
    //             \Carbon\Carbon::create($year, $m, 1)->format('M Y') => [
    //                 'earnings' => 0,
    //                 'ewt' => 0,
    //                 'tax_rate' => 0,
    //                 'tax_slab' => 'N/A'
    //             ]
    //         ]);

    //         // Get tax brackets
    //         $brackets = DB::table('ewt_tax_brackets')
    //             ->orderBy('min_salary')
    //             ->get();

    //         // Base query with correct relationships
    //         $query = Payroll::with([
    //             'employees.employee.resortAdmin',
    //             'employees.employee.position',
    //             'employees.employee.department',
    //             'reviews' => function($q) {
    //                 $q->select('payroll_id', 'employee_id', 'earnings_basic', 'earnings_allowance');
    //             },
    //             'deductions' => function($q) {
    //                 $q->select('payroll_id', 'employee_id', 'ewt');
    //             },
    //             'serviceCharges' => function($q) {
    //                 $q->select('payroll_id', 'employee_id', 'service_charge_amount');
    //             }
    //         ])
    //         ->whereYear('start_date', $year)
    //         ->whereHas('employees.employee.resortAdmin', function($q) use ($resort_id) {
    //             $q->where('resort_id', $resort_id);
    //         });

    //         // Apply filters
    //         if ($request->has('search') && $request->search) {
    //             $query->whereHas('employees.employee.resortAdmin', function($q) use ($request) {
    //                 $q->where('first_name', 'like', '%'.$request->search.'%')
    //                   ->orWhere('last_name', 'like', '%'.$request->search.'%');
    //             });
    //         }

    //         if ($request->has('department') && $request->department) {
    //             $query->whereHas('employees.employee', function($q) use ($request) {
    //                 $q->where('Dept_id', $request->department);
    //             });
    //         }

    //         if ($request->has('position') && $request->position) {
    //             $query->whereHas('employees.employee', function($q) use ($request) {
    //                 $q->where('Position_id', $request->position);
    //             });
    //         }

    //         $payrolls = $query->get();

    //         // Process data
    //         $employeesData = [];

    //         foreach ($payrolls as $payroll) {
    //             foreach ($payroll->employees as $payrollEmployee) {
    //                 $employee = $payrollEmployee->employee;
    //                 $employee_id = $employee->id;
    //                 $monthLabel = \Carbon\Carbon::parse($payroll->start_date)->format('M Y');

    //                 // Get related records
    //                 $review = $payroll->reviews->firstWhere('employee_id', $employee_id);
    //                 $deduction = $payroll->deductions->firstWhere('employee_id', $employee_id);
    //                 $serviceCharge = $payroll->serviceCharges->firstWhere('employee_id', $employee_id);

    //                 // Calculate earnings
    //                 $earnings = ($review->earnings_basic ?? 0)
    //                           + ($review->earnings_allowance ?? 0)
    //                           + ($serviceCharge->service_charge_amount ?? 0);

    //                 $ewt = $deduction->ewt ?? 0;
    //                 $totalMVR = $earnings * $conversionRate;

    //                 // Determine tax slab
    //                 $tax_rate = 0;
    //                 $tax_slab = 'N/A';

    //                 foreach ($brackets as $bracket) {
    //                     if ($totalMVR >= $bracket->min_salary &&
    //                         ($totalMVR <= ($bracket->max_salary ?? PHP_FLOAT_MAX))) {
    //                         $tax_rate = $bracket->tax_rate;
    //                         $tax_slab = "Slab {$bracket->id}";
    //                         break;
    //                     }
    //                 }

    //                 // Initialize employee if not exists
    //                 if (!isset($employeesData[$employee_id])) {
    //                     $employeesData[$employee_id] = [
    //                         'id' => $employee->Emp_id,
    //                         'name' => trim($employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name),
    //                         'profile_picture' => Common::getResortUserPicture($employee->Admin_Parent_id),
    //                         'department' => $employee->department->name ?? 'N/A',
    //                         'departmentCode' => $employee->department->code ?? 'N/A',
    //                         'position' => $employee->position->position_title ?? 'N/A',
    //                         'monthly_data' => $months->toArray(),
    //                         'total_earnings' => 0,
    //                         'total_ewt' => 0,
    //                         'resigned' => $employee->resignation_date ? [
    //                             'date' => $employee->resignation_date,
    //                             'formatted_date' => \Carbon\Carbon::parse($employee->resignation_date)->format('d M Y')
    //                         ] : null
    //                     ];
    //                 }

    //                 // Update monthly data
    //                 $employeesData[$employee_id]['monthly_data'][$monthLabel] = [
    //                     'earnings' => round($earnings, 2),
    //                     'ewt' => round($ewt, 2),
    //                     'tax_rate' => $tax_rate,
    //                     'tax_slab' => $tax_slab
    //                 ];

    //                 // Update totals
    //                 $employeesData[$employee_id]['total_earnings'] += $earnings;
    //                 $employeesData[$employee_id]['total_ewt'] += $ewt;
    //             }
    //         }

    //         // Final processing
    //         $processedData = array_values($employeesData);
    //         $monthHeaders = array_keys($months->toArray());

    //         // Get total count before pagination
    //         $totalRecords = count($employeesData);
    //         $processedData = array_values($employeesData);

    //         // Apply DataTables pagination
    //         $start = $request->input('start', 0);
    //         $length = $request->input('length', 10);
    //         $paginatedData = array_slice($processedData, $start, $length);

    //         return response()->json([
    //             'draw' => $request->input('draw', 0),
    //             'recordsTotal' => $totalRecords,
    //             'recordsFiltered' => $totalRecords, // Same as total since we're not filtering after query
    //             'data' => $paginatedData,
    //             'months' => $monthHeaders,
    //             'success' => true
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error("EWT Data Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to load EWT data',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getEWTData(Request $request, $year = null)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $sitesettings = ResortSiteSettings::where('resort_id', $resort_id)->first();
            $conversionRate = $sitesettings['DollertoMVR'] ?? 15.42;

            // Initialize all months
            $months = collect(range(1, 12))->mapWithKeys(fn($m) => [
                \Carbon\Carbon::create($year, $m, 1)->format('M Y') => [
                    'earnings' => 0,
                    'ewt' => 0,
                    'tax_rate' => 0,
                    'tax_slab' => 'N/A'
                ]
            ]);

            // Get tax brackets
            $brackets = DB::table('ewt_tax_brackets')
                ->orderBy('min_salary')
                ->get();

            // Base query with correct relationships
            $query = Payroll::with([
                'employees.employee',
                'employees.employee.resortAdmin',
                'employees.employee.position',
                'employees.employee.department',
                'employees.employee.resignation', // Add resignation relationship
                'reviews' => function($q) {
                    $q->select('payroll_id', 'employee_id', 'earnings_basic', 'earnings_allowance');
                },
                'deductions' => function($q) {
                    $q->select('payroll_id', 'employee_id', 'ewt');
                },
                'serviceCharges' => function($q) {
                    $q->select('payroll_id', 'employee_id', 'service_charge_amount');
                }
            ])
            ->whereYear('start_date', $year)
            ->whereHas('employees.employee', function($q)  {
                $q->where('status', 'Active');
            })
            ->whereHas('employees.employee.resortAdmin', function($q) use ($resort_id) {
                $q->where('resort_id', $resort_id);
            });

            // Apply filters
            if ($request->has('search') && $request->search) {
                $query->whereHas('employees.employee.resortAdmin', function($q) use ($request) {
                    $q->where('first_name', 'like', '%'.$request->search.'%')
                    ->orWhere('last_name', 'like', '%'.$request->search.'%');
                });
            }
           if ($request->has('department') && $request->department) {
                Log::info('Filtering by department ID', ['value' => $request->department]);

                $query->whereHas('employees.employee', function ($q) use ($request) {
                    $q->where('Dept_id', $request->department);
                });
            }

            if ($request->has('position') && $request->position) {
                $query->whereHas('employees.employee', function($q) use ($request) {
                    $q->where('Position_id', $request->position);
                });
            }

            $payrolls = $query->get();
            // dd($payrolls);

            // Process data
            $employeesData = [];

            foreach ($payrolls as $payroll) {
                // dd($payroll->employees);
                foreach ($payroll->employees as $payrollEmployee) {

                    $employee = $payrollEmployee->employee;

                    if ($request->filled('department') && $employee->Dept_id != $request->department) {
                        continue;
                    }
                    if ($request->filled('position') && $employee->Position_id != $request->position) {
                        continue;
                    }
                    if ($request->filled('search')) {
                        $searchTerm = strtolower($request->search);
                        $first = strtolower($employee->resortAdmin->first_name ?? '');
                        $last = strtolower($employee->resortAdmin->last_name ?? '');
                        if (!str_contains($first, $searchTerm) && !str_contains($last, $searchTerm)) {
                            continue;
                        }
                    }
                    $employee_id = $employee->id;
                    $monthLabel = \Carbon\Carbon::parse($payroll->start_date)->format('M Y');

                    // Get related records
                    $review = $payroll->reviews->firstWhere('employee_id', $employee_id);
                    $deduction = $payroll->deductions->firstWhere('employee_id', $employee_id);
                    $serviceCharge = $payroll->serviceCharges->firstWhere('employee_id', $employee_id);
                    $resignation = $employee->resignation; // Get resignation data

                    // Calculate earnings
                    $earnings = ($review->earnings_basic ?? 0)
                            + ($review->earnings_allowance ?? 0)
                            + ($serviceCharge->service_charge_amount ?? 0);

                    $ewt = $deduction->ewt ?? 0;
                    $totalMVR = $earnings * $conversionRate;

                    // Determine tax slab
                    $tax_rate = 0;
                    $tax_slab = 'N/A';

                    foreach ($brackets as $bracket) {
                        if ($totalMVR >= $bracket->min_salary &&
                            ($totalMVR <= ($bracket->max_salary ?? PHP_FLOAT_MAX))) {
                            $tax_rate = $bracket->tax_rate;
                            $tax_slab = "Slab {$bracket->id}";
                            break;
                        }
                    }

                    // Initialize employee if not exists
                    if (!isset($employeesData[$employee_id])) {
                        $employeesData[$employee_id] = [
                            'id' => $employee->Emp_id,
                            'name' => trim($employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name),
                            'profile_picture' => Common::getResortUserPicture($employee->Admin_Parent_id),
                            'department' => $employee->department->name ?? 'N/A',
                            'departmentCode' => $employee->department->code ?? 'N/A',
                            'position' => $employee->position->position_title ?? 'N/A',
                            'monthly_data' => $months->toArray(),
                            'total_earnings' => 0,
                            'total_ewt' => 0,
                            'resigned' => $resignation ? [
                                'date' => $resignation->resignation_date,
                                'formatted_date' => \Carbon\Carbon::parse($resignation->resignation_date)->format('d M Y'),
                                'last_working_day' => $resignation->last_working_day,
                                'status' => $resignation->status
                            ] : null
                        ];
                    }

                    // Update monthly data
                    $employeesData[$employee_id]['monthly_data'][$monthLabel] = [
                        'earnings' => round($earnings, 2),
                        'ewt' => round($ewt, 2),
                        'tax_rate' => $tax_rate,
                        'tax_slab' => $tax_slab
                    ];

                    // Update totals
                    $employeesData[$employee_id]['total_earnings'] += $earnings;
                    $employeesData[$employee_id]['total_ewt'] += $ewt;
                }
            }

            // Final processing
            $processedData = array_values($employeesData);
            $monthHeaders = array_keys($months->toArray());

            // Get total count before pagination
            $totalRecords = count($employeesData);
            $processedData = array_values($employeesData);

            // Apply DataTables pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $paginatedData = array_slice($processedData, $start, $length);

            return response()->json([
                'draw' => $request->input('draw', 0),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $paginatedData,
                'months' => $monthHeaders,
                'success' => true,
                  'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
        } catch (\Exception $e) {
            Log::error("EWT Data Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load EWT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getformerEmployeesEWTData(Request $request, $year){
        $page_title ='Former Employees EWT';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Inactive', 'Terminated','Resigned'])->get();
        $deductions = Deduction::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        if($request->ajax()){
            try {
                $resort_id = $this->resort->resort_id;
                $sitesettings = ResortSiteSettings::where('resort_id', $resort_id)->first();
                $conversionRate = $sitesettings['DollertoMVR'] ?? 15.42;

                // Initialize all months
                $months = collect(range(1, 12))->mapWithKeys(fn($m) => [
                    \Carbon\Carbon::create($year, $m, 1)->format('M Y') => [
                        'earnings' => 0,
                        'ewt' => 0,
                        'tax_rate' => 0,
                        'tax_slab' => 'N/A'
                    ]
                ]);

                // Get tax brackets
                $brackets = DB::table('ewt_tax_brackets')
                    ->orderBy('min_salary')
                    ->get();

                // Base query with correct relationships
                $query = Payroll::with([
                    'employees.employee.resortAdmin',
                    'employees.employee.position',
                    'employees.employee.department',
                    'employees.employee.resignation', // Add resignation relationship
                    'reviews' => function($q) {
                        $q->select('payroll_id', 'employee_id', 'earnings_basic', 'earnings_allowance');
                    },
                    'deductions' => function($q) {
                        $q->select('payroll_id', 'employee_id', 'ewt');
                    },
                    'serviceCharges' => function($q) {
                        $q->select('payroll_id', 'employee_id', 'service_charge_amount');
                    }
                ])
                ->whereYear('start_date', $year)
                ->whereIn('employees.employee.status', ['Inactive', 'Terminated', 'Resigned'])
                ->whereHas('employees.employee.resortAdmin', function($q) use ($resort_id) {
                    $q->where('resort_id', $resort_id);
                });

                // Apply filters
                if ($request->has('search') && $request->search) {
                    $query->whereHas('employees.employee.resortAdmin', function($q) use ($request) {
                        $q->where('first_name', 'like', '%'.$request->search.'%')
                        ->orWhere('last_name', 'like', '%'.$request->search.'%');
                    });
                }

                if ($request->has('department') && $request->department) {
                    $query->whereHas('employees.employee', function($q) use ($request) {
                        $q->where('Dept_id', $request->department);
                    });
                }

                if ($request->has('position') && $request->position) {
                    $query->whereHas('employees.employee', function($q) use ($request) {
                        $q->where('Position_id', $request->position);
                    });
                }

                $payrolls = $query->get();

                // Process data
                $employeesData = [];

                foreach ($payrolls as $payroll) {
                    foreach ($payroll->employees as $payrollEmployee) {
                        $employee = $payrollEmployee->employee;
                        $employee_id = $employee->id;
                        $monthLabel = \Carbon\Carbon::parse($payroll->start_date)->format('M Y');

                        // Get related records
                        $review = $payroll->reviews->firstWhere('employee_id', $employee_id);
                        $deduction = $payroll->deductions->firstWhere('employee_id', $employee_id);
                        $serviceCharge = $payroll->serviceCharges->firstWhere('employee_id', $employee_id);
                        $resignation = $employee->resignation; // Get resignation data

                        // Calculate earnings
                        $earnings = ($review->earnings_basic ?? 0)
                                + ($review->earnings_allowance ?? 0)
                                + ($serviceCharge->service_charge_amount ?? 0);

                        $ewt = $deduction->ewt ?? 0;
                        $totalMVR = $earnings * $conversionRate;

                        // Determine tax slab
                        $tax_rate = 0;
                        $tax_slab = 'N/A';

                        foreach ($brackets as $bracket) {
                            if ($totalMVR >= $bracket->min_salary &&
                                ($totalMVR <= ($bracket->max_salary ?? PHP_FLOAT_MAX))) {
                                $tax_rate = $bracket->tax_rate;
                                $tax_slab = "Slab {$bracket->id}";
                                break;
                            }
                        }

                        // Initialize employee if not exists
                        if (!isset($employeesData[$employee_id])) {
                            $employeesData[$employee_id] = [
                                'id' => $employee->Emp_id,
                                'name' => trim($employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name),
                                'profile_picture' => Common::getResortUserPicture($employee->Admin_Parent_id),
                                'department' => $employee->department->name ?? 'N/A',
                                'departmentCode' => $employee->department->code ?? 'N/A',
                                'position' => $employee->position->position_title ?? 'N/A',
                                'monthly_data' => $months->toArray(),
                                'total_earnings' => 0,
                                'total_ewt' => 0,
                                'resigned' => $resignation ? [
                                    'date' => $resignation->resignation_date,
                                    'formatted_date' => \Carbon\Carbon::parse($resignation->resignation_date)->format('d M Y'),
                                    'last_working_day' => $resignation->last_working_day,
                                    'status' => $resignation->status
                                ] : null
                            ];
                        }

                        // Update monthly data
                        $employeesData[$employee_id]['monthly_data'][$monthLabel] = [
                            'earnings' => round($earnings, 2),
                            'ewt' => round($ewt, 2),
                            'tax_rate' => $tax_rate,
                            'tax_slab' => $tax_slab
                        ];

                        // Update totals
                        $employeesData[$employee_id]['total_earnings'] += $earnings;
                        $employeesData[$employee_id]['total_ewt'] += $ewt;
                    }
                }

                // Final processing
                $processedData = array_values($employeesData);
                $monthHeaders = array_keys($months->toArray());

                // Get total count before pagination
                $totalRecords = count($employeesData);
                $processedData = array_values($employeesData);

                // Apply DataTables pagination
                $start = $request->input('start', 0);
                $length = $request->input('length', 10);
                $paginatedData = array_slice($processedData, $start, $length);

                return response()->json([
                    'draw' => $request->input('draw', 0),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecords,
                    'data' => $paginatedData,
                    'months' => $monthHeaders,
                    'success' => true
                ]);
            } catch (\Exception $e) {
                Log::error("EWT Data Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load EWT data',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return view('resorts.payroll.EWT.former-employees',compact('page_title','positions','departments','deductions','employees'));
    }

}
