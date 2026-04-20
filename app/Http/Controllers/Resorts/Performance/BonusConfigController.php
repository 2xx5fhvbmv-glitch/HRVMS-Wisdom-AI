<?php

namespace App\Http\Controllers\Resorts\Performance;

use Auth;
use Validator;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PerformanceBonusConfig;

class BonusConfigController extends Controller
{
    public $resort = '';

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index()
    {
        if (!$this->resort) return abort(403, 'Unauthorized');

        $page_title = 'Bonus Configuration';
        $resort_id  = $this->resort->resort_id;

        // Same rank set as Benefit Grid (config/settings.php → eligibilty)
        $ranks = config('settings.eligibilty') ?? [];

        $existing = PerformanceBonusConfig::where('resort_id', $resort_id)
            ->get()
            ->keyBy('rank');

        $rows = [];
        foreach ($ranks as $rankKey => $rankLabel) {
            $row = $existing->get($rankKey);
            $rows[] = (object)[
                'rank'             => $rankKey,
                'rank_label'       => $rankLabel,
                'bonus_percentage' => $row ? $row->bonus_percentage : null,
                'month'            => $row ? $row->month : null,
                'year'             => $row ? $row->year : null,
            ];
        }

        $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $currentYear = (int) date('Y');
        $years = range($currentYear - 2, $currentYear + 5);

        return view('resorts.Performance.BonusConfig.index', compact('page_title', 'rows', 'months', 'years'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rank'             => 'required|integer',
            'bonus_percentage' => 'nullable|numeric|min:0|max:1000',
            'month'            => 'nullable|string|max:20',
            'year'             => 'nullable|integer|min:2000|max:2100',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ranks = config('settings.eligibilty') ?? [];
        if (!array_key_exists((int) $request->rank, $ranks)) {
            return response()->json(['success' => false, 'message' => 'Invalid rank.'], 422);
        }

        $resort_id = $this->resort->resort_id;

        PerformanceBonusConfig::updateOrCreate(
            ['resort_id' => $resort_id, 'rank' => (int) $request->rank],
            [
                'bonus_percentage' => $request->bonus_percentage,
                'month'            => $request->month ?: null,
                'year'             => $request->year ?: null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bonus percentage saved.',
            'rank'    => (int) $request->rank,
            'value'   => $request->bonus_percentage,
            'month'   => $request->month,
            'year'    => $request->year,
        ]);
    }
}
