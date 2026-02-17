<?php

namespace App\Http\Controllers\Resorts\People\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\EmployeePromotion;
use App\Models\EmployeePromotionApproval;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use Auth;
use Config;
use DB;
use Common;

class DashboardController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Promotion Dashboard';
        $resort_id = $this->resort->resort_id;
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $total_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Active','On Leave'])->count();
        $pending_promotion = EmployeePromotion::where('resort_id',$resort_id)->where('status','Pending')->count();
        $approved_promotion = EmployeePromotion::where('resort_id',$resort_id)->where('status','Approved')->count();
        $employees = Employee::with(['resortAdmin','position','department'])->where('resort_id',$resort_id)->where('status','Active')->get();
        $currentYear = now()->year;
        $lastYear = now()->subYear()->year;

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $thisYearData = EmployeePromotion::selectRaw('MONTH(effective_date) as month, COUNT(*) as total')
            ->whereYear('effective_date', $currentYear)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $lastYearData = EmployeePromotion::selectRaw('MONTH(effective_date) as month, COUNT(*) as total')
            ->whereYear('effective_date', $lastYear)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Ensure zero for missing months
        $thisYearCounts = array_fill(1, 12, 0);
        $lastYearCounts = array_fill(1, 12, 0);

        foreach ($thisYearData as $month => $count) {
            $thisYearCounts[$month] = $count;
        }

        foreach ($lastYearData as $month => $count) {
            $lastYearCounts[$month] = $count;
        }

        return view('resorts.people.promotion.hrdashboard',compact('page_title','total_employees','pending_promotion','approved_promotion','positions','employees','departments','labels','thisYearCounts','lastYearCounts','currentYear','lastYear'));
    }

    public function filter(Request $request)
    {
        // dd("test");
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInUserId = $loggedInEmployee->id;
        $rank = config('settings.Position_Rank');
        $current_rank = $loggedInEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHR = ($available_rank === "HR");
        $query = EmployeePromotion::with(['employee.resortAdmin', 'employee.department'])->where('resort_id',$this->resort->resort_id);

        if ($request->employee) {
            $query->where('employee_id', $request->employee);
        }

        if ($request->department) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('Dept_id', $request->department);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('effective_date', $request->date);
        }


        return datatables()->of($query)
            ->addColumn('promotion_id', fn($row) => '#' . $row->employee->Emp_id)
            ->addColumn('employee_name', function ($row) {
                return '
                    <div class="tableUser-block">
                        <div class="img-circle">
                            <img src="' . Common::getResortUserPicture($row->employee->Admin_Parent_id ?? null) . '" alt="user">
                        </div>
                        <span>' . $row->employee->resortAdmin->full_name . '</span>
                    </div>';
            })
            ->addColumn('current_position', fn($row) => optional($row->currentPosition)->position_title ?? '—')
            ->addColumn('new_position', fn($row) => optional($row->newPosition)->position_title ?? '—')
            ->addColumn('current_salary', fn($row) => '$' . number_format($row->current_salary))
            ->addColumn('new_salary', fn($row) => '$' . number_format($row->new_salary))
            ->addColumn('effective_date', function ($row) {
                return date('d M Y', strtotime($row->effective_date));
            })
            ->addColumn('status', function ($row) {
                return match ($row->status) {
                    'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                    'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                    'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                    default    => '<span class="badge badge-themeWarning">Pending</span>',
                };
            })
            ->addColumn('action', function ($row) use ($isHR) {
                $viewBtn = '';
                $editBtn = '';

                if ($isHR) {
                    $viewUrl = route('promotion.details', ['id' => base64_encode($row->id)]);
                    $viewBtn = '<a href="' . $viewUrl . '" class="btn-tableIcon btnIcon-skyblue"><i class="fa-regular fa-eye"></i></a>';

                    // Only show edit if status is Pending or On Hold
                    if (in_array($row->status, ['Pending', 'On Hold'])) {
                        $editBtn = '<a href="#" class="btn-tableIcon btnIcon-blue edit-row-btn" data-id="' . $row->id . '"><i class="fa-regular fa-pen"></i></a>';
                    }
                } else {
                    $viewUrl = route('promotion.approval', ['id' => base64_encode($row->id)]);
                    $viewBtn = '<a href="' . $viewUrl . '" class="btn-tableIcon btnIcon-skyblue"><i class="fa-regular fa-eye"></i></a>';
                }

                return $viewBtn . $editBtn;
            })

            ->rawColumns(['employee_name', 'effective_date','status', 'action'])
            ->make(true);
    }

    public function getBasicSalaryData(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $filter = $request->input('filter', 'month');
        $year = $request->input('year', now()->year);

        $labels = [];
        $currentBasic = [];
        $newBasic = [];

        if ($filter === 'month') {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            $monthlyData = DB::table('employee_promotions')
                ->selectRaw('MONTH(effective_date) as month,
                            SUM(current_salary) as current,
                            SUM(new_salary) as new')
                ->whereYear('effective_date', $year)
                ->where('resort_id', $resort_id)
                ->groupBy(DB::raw('MONTH(effective_date)'))
                ->get()
                ->keyBy('month');

            for ($i = 1; $i <= 12; $i++) {
                $currentBasic[] = $monthlyData[$i]->current ?? 0;
                $newBasic[] = $monthlyData[$i]->new ?? 0;
            }

        } elseif ($filter === 'quarter') {
            $labels = ['Q1', 'Q2', 'Q3', 'Q4'];

            $quarterlyData = DB::table('employee_promotions')
                ->selectRaw('QUARTER(effective_date) as quarter,
                            SUM(current_salary) as current,
                            SUM(new_salary) as new')
                ->whereYear('effective_date', $year)
                ->where('resort_id', $resort_id)
                ->groupBy(DB::raw('QUARTER(effective_date)'))
                ->get()
                ->keyBy('quarter');

            for ($i = 1; $i <= 4; $i++) {
                $currentBasic[] = $quarterlyData[$i]->current ?? 0;
                $newBasic[] = $quarterlyData[$i]->new ?? 0;
            }

        } elseif ($filter === 'year') {
            $startYear = now()->year - 3;
            $endYear = now()->year;
            $labels = [];

            $yearlyData = DB::table('employee_promotions')
                ->selectRaw('YEAR(effective_date) as year,
                            SUM(current_salary) as current,
                            SUM(new_salary) as new')
                ->whereBetween(DB::raw('YEAR(effective_date)'), [$startYear, $endYear])
                ->where('resort_id', $resort_id)
                ->groupBy(DB::raw('YEAR(effective_date)'))
                ->get()
                ->keyBy('year');

            for ($y = $startYear; $y <= $endYear; $y++) {
                $labels[] = $y;
                $currentBasic[] = $yearlyData[$y]->current ?? 0;
                $newBasic[] = $yearlyData[$y]->new ?? 0;
            }
        }

        return response()->json([
            'labels' => $labels,
            'currentBasic' => $currentBasic,
            'newBasic' => $newBasic,
        ]);
    }


}