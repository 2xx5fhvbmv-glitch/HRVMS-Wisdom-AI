<?php

namespace App\Http\Controllers\Resorts\Performance;
use DB;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PerformanceKpiChild;
use App\Models\PerformanceKpiParent;
class KpiController extends Controller
{

    public $resort='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $reporting_to = $this->resort->GetEmployee->id;
        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }
    public function create()
    {
        if(Common::checkRouteWisePermission('Performance.kpi.KpiList',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ="Create KPI";
        return view('resorts.Performance.Kpi.create',compact('page_title'));
    }
    public function PerformanceKpiStore(Request $request)
    {
        $property_goal = $request->property_goal;
        $PropertyGoalbudget = $request->PropertyGoalbudget;
        $PropertyGoalweightage = $request->PropertyGoalweightage;
        $PropertyGoalscore = $request->PropertyGoalscore;
        $budget = $request->budget;
        $weightage = $request->weightage;
        $score = $request->score;

        DB::beginTransaction();
        try
        {
            $kpi_parents_id = PerformanceKpiParent::create([
                                'resort_id'=>$this->resort->resort_id,
                                'property_goal'=>$property_goal,
                                'PropertyGoalbudget'=>$PropertyGoalbudget,
                                'PropertyGoalweightage'=>$PropertyGoalweightage,
                                'PropertyGoalscore'=>$PropertyGoalscore,
                            ]);
            if(isset($kpi_parents_id))
            {
                foreach ($budget as $key => $value)
                {
                    $weightage = $request->weightage[$key];
                    $score = $request->score[$key];
                    PerformanceKpiChild::create([
                        'kpi_parents_id'=>$kpi_parents_id->id,
                        'budget'=>$value,
                        'weightage'=>$PropertyGoalbudget,
                        'score'=>$score,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' =>true,'message'=>'Performance KPI successfully'], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' =>false,'error' => 'Failed to Performance KPI','message'=>'Failed to Assign Bed  '], 500);
        }

    }
    public function KpiList(Request $request)
    {

        $Year = $request->Year;
        if($Year =="All")
        {
            $Year= Date("Y");
        }
        $searchTerm = $request->searchTerm;
        $kpiList = PerformanceKpiParent::with('childrenKpi')
        ->where('resort_id', $this->resort->resort_id)
        ->when($Year, function ($query) use ($Year) {
            $query->whereYear('created_at', $Year);
        })
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('property_goal', 'LIKE', "%{$searchTerm}%")  // Replace with actual column names
                  ->orWhere('property_goal', 'LIKE', "%{$searchTerm}%");
            });
        })
        ->get();
        if($request->ajax())
        {
            return datatables()->of($kpiList)
            ->editColumn('PropertyGoals', function ($row) {
              return   ucfirst($row->property_goal);
            })
            ->editColumn('budget', function ($row) {
                return   ucfirst($row->PropertyGoalbudget);
            })
            ->editColumn('Actual', function ($row) {
                return '-';
            })
            ->editColumn('Value', function ($row) {
                return '-';
            })
            ->editColumn('Result', function ($row) {
                return '-';
            })

            ->editColumn('Score', function ($row)
            {
                return '-';
            })
            ->editColumn('ScoreInPercentage', function ($row)
            {
                return '-';
            })
            ->editColumn('IndividualGoals', function ($row)
            {
                return '-';
            })
            ->rawColumns(['PropertyGoals','budget','Actual','Result','Value','Score','ScoreInPercentage','IndividualGoals'])
            ->make(true);
        }
        $page_title =" KPI List";
        return view('resorts.Performance.Kpi.index',compact('page_title'));

    }
}
