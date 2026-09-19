<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\CasualPositionPayConfig;
use App\Models\ResortPosition;
use App\Models\ResortSiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * §35 — payment-model toggle + per-Casual-position basic salary/commission.
 * Configured once per position, reused every month by the Casual payroll
 * run (Payroll\CasualPayrollController) rather than re-entered manually.
 */
class CasualPaymentModelController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('people.casualPaymentModel.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Casuals — Payment Model';
        $resort_id = $this->resort->resort_id;

        $settings = ResortSiteSettings::where('resort_id', $resort_id)->first();
        $paymentModel = $settings->casual_payment_model ?? 'lump_sum';

        $positions = ResortPosition::with(['department', 'payConfig' => function ($q) use ($resort_id) {
                $q->where('resort_id', $resort_id);
            }])
            ->where('resort_id', $resort_id)
            ->where('employee_category', 'Casual')
            ->where('status', 'active')
            ->orderBy('position_title')
            ->get();

        return view('resorts.people.config.casual-payment-model.index', compact('page_title', 'paymentModel', 'positions'));
    }

    public function storeModel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'casual_payment_model' => 'required|in:lump_sum,direct_pay',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        ResortSiteSettings::updateOrCreate(
            ['resort_id' => $this->resort->resort_id],
            ['casual_payment_model' => $request->casual_payment_model]
        );

        return response()->json(['success' => true, 'message' => 'Payment model saved.']);
    }

    public function storePositionPay(Request $request)
    {
        $resortId = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'position_id' => [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists('resort_positions', 'id')
                    ->where('resort_id', $resortId)
                    ->where('employee_category', 'Casual'),
            ],
            'basic_salary' => 'required|numeric|min:0',
            'basic_salary_currency' => 'required|in:USD,MVR',
            'commission_amount' => 'required|numeric|min:0',
            'commission_currency' => 'required|in:USD,MVR',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 400);
        }

        $config = CasualPositionPayConfig::updateOrCreate(
            ['resort_id' => $resortId, 'position_id' => $request->position_id],
            [
                'basic_salary' => $request->basic_salary,
                'basic_salary_currency' => $request->basic_salary_currency,
                'commission_amount' => $request->commission_amount,
                'commission_currency' => $request->commission_currency,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Position pay saved.', 'config' => $config]);
    }
}
