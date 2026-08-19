<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeNoticePeriod;
use App\Models\ResortPosition;
use Auth;
use Config;
use DB;

class NoticePeriodController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    Public function index(){
        $page_title ='Notice Period';
        $resort_id = $this->resort->resort_id;
       $emp_grade = config('settings.eligibilty');
        return view('resorts.people.config.notice-period.list',compact('page_title','emp_grade'));
    }
    
    public function list(Request $request){
        if($request->ajax())
        {
            $noticePeriod = EmployeeNoticePeriod::where('resort_id', $this->resort->resort_id)->get();

            return datatables()->of($noticePeriod)
                ->addColumn('immediate_release', function ($noticePeriod) {
                        $immidate_release = '';
                    if($noticePeriod->immediate_release == '1'){
                        $immidate_release = '<span class="badge badge-themeSuccess">Yes</span>';
                    }else{
                        $immidate_release = '<span class="badge badge-themeDanger">No</span>';
                    }
                    return $immidate_release;
                })
                ->addColumn('action', function ($noticePeriod) {
                    $id = base64_encode($noticePeriod->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['immediate_release','action']) // Ensure buttons are rendered as HTML
                ->make(true);
        }
    }


    public function store(Request $request){
         $data = $request->notice_periods;

        if (!is_array($data) || empty($data)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No notice period rows submitted.',
            ], 422);
        }

        foreach ($data as $key => $value) {
            // Mirror update()'s logic: an "immediate release" notice period
            // has no period — store() previously kept the posted days value,
            // leaving a stray period on immediate-release rows.
            $immediateRelease = @$value['immediate_release'] ?? 0;

            // Server-side guard mirroring the frontend rule: a row needs a
            // title, and a positive days value unless immediate release is
            // enabled. Previously this was JS-only and a bypassed request
            // could persist a meaningless row.
            if (empty($value['title'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Title is required for every notice period row.',
                ], 422);
            }
            if ($immediateRelease != '1' && (!isset($value['days']) || !is_numeric($value['days']) || $value['days'] <= 0)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Notice period days must be a positive number unless Immediate Release is enabled.',
                ], 422);
            }

            $emp_notice_period = EmployeeNoticePeriod::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id,
                'title' => $value['title'],
            ],
            [
                'period' => $immediateRelease == '1' ? null : (@$value['days']),
                'immediate_release' => $immediateRelease,
            ]
        );
        }
         return response()->json([
            'status' => 'success',
            'message' => 'Notice Period added successfully.',
        ]);
    }

    public function update(Request $request){
        $id = base64_decode($request->id);

        // Was ->find($id) with no resort filter — any resort-admin could
        // update another resort's notice-period rule by id.
        $emp_notice_period = EmployeeNoticePeriod::where('resort_id', $this->resort->resort_id)->find($id);

        if(!$emp_notice_period){
            return response()->json([
                'success' => false,
                'message' => 'Notice Period rule not found.',
            ], 404);
        }

        $emp_notice_period->title = $request->title;
        // Save period AS POSTED by HR. The previous logic forced period
        // to null whenever immediate_release was ticked — silently lossy
        // because Eloquent then saw no change vs the prior row and
        // skipped the UPDATE entirely (visible as a stale `updated_at`).
        // The two fields aren't mutually exclusive at the data layer:
        // `immediate_release` is just a flag on the grade ("this grade
        // is allowed to skip notice"), while `period` is the configured
        // notice length for grades that DO serve it. F&F's notice-period
        // charge calculation (FinalSettlementService:233-261) reads only
        // `period` and ignores immediate_release entirely, so respecting
        // both fields matches the downstream consumer.
        $emp_notice_period->period            = ($request->period === '' || $request->period === null)
            ? null
            : $request->period;
        $emp_notice_period->immediate_release = $request->immediate_release ?? 0;
        $emp_notice_period->save();

        return response()->json([
            'success' => true,
            'message' => 'Notice Period updated successfully.',
        ]);
    }

    public function destroy(Request $request){
        $id = base64_decode($request->id);
        // Was ->find($id) with no resort filter — same cross-tenant gap
        // as update() above.
        $emp_notice_period = EmployeeNoticePeriod::where('resort_id', $this->resort->resort_id)->find($id);
        if($emp_notice_period){
            $emp_notice_period->delete();
        }
         return response()->json([
            'success' => true,
            'message' => 'Notice Period deleted successfully.',
        ]);
    }
}