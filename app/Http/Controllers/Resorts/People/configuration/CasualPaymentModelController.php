<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\CasualEmployeePayOverride;
use App\Models\CasualPositionPayConfig;
use App\Models\ResortPosition;
use App\Models\ResortSiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * WP3/§35 — the ONE salary source for Casual/Intern (D3). Level 1: per-
 * position basic salary + (Casual-only) service-provider commission —
 * covers both Casual and Intern positions. Level 2: optional per-person
 * custom salary override on the same screen, taking precedence over the
 * position rate — see Common::casualInternBasicSalary().
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

        $page_title = 'Casuals & Interns — Payment Model';
        $resort_id = $this->resort->resort_id;

        $settings = ResortSiteSettings::where('resort_id', $resort_id)->first();
        $paymentModel = $settings->casual_payment_model ?? 'lump_sum';

        // WP3 (D3) — position pay must also cover Intern positions, not
        // just Casual (§35's original screen only handled Casual).
        $positions = ResortPosition::with([
                'department',
                'payConfig' => function ($q) use ($resort_id) {
                    $q->where('resort_id', $resort_id);
                },
                'employees.resortAdmin',
                'employees.payOverride',
            ])
            ->where('resort_id', $resort_id)
            ->whereIn('employee_category', ['Casual', 'Intern'])
            ->where('status', 'active')
            ->orderBy('employee_category')
            ->orderBy('position_title')
            ->get();

        return view('resorts.people.config.casual-payment-model.index', compact('page_title', 'paymentModel', 'positions'));
    }

    public function storeModel(Request $request)
    {
        if (Common::checkRouteWisePermission('people.casualPaymentModel.index', config('settings.resort_permissions.create')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

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
        if (Common::checkRouteWisePermission('people.casualPaymentModel.index', config('settings.resort_permissions.create')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $resortId = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'position_id' => [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists('resort_positions', 'id')
                    ->where('resort_id', $resortId)
                    ->whereIn('employee_category', ['Casual', 'Intern']),
            ],
            'basic_salary' => 'required|numeric|min:0',
            'basic_salary_currency' => 'required|in:USD,MVR',
            // Service-provider commission is a Casual concept (the
            // resort's own agency-fee record) — not applicable to Interns,
            // who have no service provider. Defaults to 0 when omitted.
            'commission_amount' => 'nullable|numeric|min:0',
            'commission_currency' => 'nullable|in:USD,MVR',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 400);
        }

        $config = CasualPositionPayConfig::updateOrCreate(
            ['resort_id' => $resortId, 'position_id' => $request->position_id],
            [
                'basic_salary' => $request->basic_salary,
                'basic_salary_currency' => $request->basic_salary_currency,
                'commission_amount' => $request->commission_amount ?? 0,
                'commission_currency' => $request->commission_currency ?? 'USD',
            ]
        );

        return response()->json(['success' => true, 'message' => 'Position pay saved.', 'config' => $config]);
    }

    /**
     * WP3 (D3) Level 2 — optional per-person custom salary, shown next to
     * (and overriding) the position rate on the same screen.
     */
    public function storeEmployeePay(Request $request)
    {
        if (Common::checkRouteWisePermission('people.casualPaymentModel.index', config('settings.resort_permissions.create')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $resortId = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'employee_id' => [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists('employees', 'id')
                    ->where('resort_id', $resortId)
                    ->whereIn('employment_type', ['Casual', 'Internship']),
            ],
            'basic_salary' => 'required|numeric|min:0',
            'basic_salary_currency' => 'required|in:USD,MVR',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 400);
        }

        $override = CasualEmployeePayOverride::updateOrCreate(
            ['resort_id' => $resortId, 'employee_id' => $request->employee_id],
            ['basic_salary' => $request->basic_salary, 'basic_salary_currency' => $request->basic_salary_currency]
        );

        return response()->json(['success' => true, 'message' => 'Custom salary saved.', 'override' => $override]);
    }

    /**
     * Clears a custom salary — the employee falls back to their position's
     * rate again.
     */
    public function destroyEmployeePay(Request $request)
    {
        if (Common::checkRouteWisePermission('people.casualPaymentModel.index', config('settings.resort_permissions.delete')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $resortId = $this->resort->resort_id;

        CasualEmployeePayOverride::where('resort_id', $resortId)
            ->where('employee_id', (int) $request->input('employee_id'))
            ->delete();

        return response()->json(['success' => true, 'message' => 'Custom salary removed — back to the position rate.']);
    }
}
