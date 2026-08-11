<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Product;
use Validator;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class ShopController extends Controller
{
    protected $user;
    protected $resort_id;

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
        }
    }

    public function employeeDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeId                                     =   $this->user->GetEmployee->id;
            $startOfMonth                                   =   Carbon::now()->startOfMonth();
            $endOfMonth                                     =   Carbon::now()->endOfMonth();
            // consentRequestHandle() sets status to 'Consented' on approval,
            // never 'Paid' ('Paid' is a separate, later payroll-settlement
            // step HR does) — filtering on 'Paid' only meant an approved
            // consent could never count as spent. Also scoped by
            // purchased_date, so a purchase made one month but only
            // consented to in a later month (a normal flow — consent isn't
            // always same-day) never showed up in "this month's spend"
            // either; updated_at (when the status actually flipped) is the
            // correct anchor for "when did this become spend".
            $totalSpentThisMonth                            =   Payment::where('emp_id', $employeeId)
                                                                    ->whereIn('status', ['Consented', 'Paid', 'Partial Paid'])
                                                                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                                                                    ->sum('price'); // or ->sum('cash_paid') based on logic
            $pendingConsentThisMonth                        =   Payment::where('emp_id', $employeeId)
                                                                    ->where('status','Pending Consent')
                                                                    ->whereBetween('purchased_date', [$startOfMonth, $endOfMonth])
                                                                    ->sum('price'); // or ->sum('cash_paid') based on logic

            $mostSpentProducts                              = DB::table('payments')
                                                                    ->join('products', 'payments.product_id', '=', 'products.id')
                                                                    ->select(
                                                                        'payments.emp_id',
                                                                        'products.name as product_name',
                                                                        DB::raw('SUM(payments.price * payments.quantity) as total_spent')
                                                                    )
                                                                    ->whereIn('payments.status', ['Consented', 'Paid', 'Partial Paid'])
                                                                    ->where('payments.emp_id', $employeeId)
                                                                    ->groupBy('payments.product_id', 'products.name', 'payments.emp_id')
                                                                    ->orderByDesc('total_spent')
                                                                    ->limit(3)
                                                                    ->get();


            // Full pending-consent list for the "Received Consent Requests" section —
            // previously only fetched a single row (->first()) and stashed it under
            // the unrelated "scan_qr" key, so the itemized list never rendered.
            // payments has no currency column of its own — price is always in
            // whatever currency the purchased product is priced in
            // (products.currency_type), which wasn't being selected at all.
            $pendingConsentList                             =   Payment::with(['shopKeeper:id,name','product:id,name,currency_type'])
                                                                    ->where('emp_id', $employeeId)
                                                                    ->where('status','Pending Consent')
                                                                    ->orderBy("created_at", "DESC")
                                                                    ->get()
                                                                    ->map(function ($payment) {
                                                                        $payment->currency = $payment->product->currency_type ?? 'USD';
                                                                        return $payment;
                                                                    });

            $shopArr                                        =   [
                'total_amount_spent'                        =>  (int)$totalSpentThisMonth,
                'pending_consent_counts'                    =>  (int)$pendingConsentThisMonth,
                'most_spent_products'                       =>  $mostSpentProducts,
                'pending_consent_requests'                  =>  $pendingConsentList,
                'scan_qr'                                   =>  '',
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Employee shop dashboard';
            $response['emp_shop_data']                      =   $shopArr;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET shop/product-details/{product_id}
     * Target of a scanned product QR code (see docs/mobile-shop-qr-scan.md
     * for the QR payload format) — the Third-Party Shop module's "scan to
     * view details/pricing/checkout" screen.
     */
    public function productDetails($productId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // The scanned QR encodes base64_encode((string) $product->id)
            // (see Shopkeeper\ConfigurationController::generateProductQr) —
            // same convention as the other base64-id "view" endpoints in
            // this app (e.g. consentRequestview).
            $productId                                      =   base64_decode($productId);
            $product                                        =   DB::table('products as p')
                                                                    ->join('shopkeepers as sk', 'sk.id', '=', 'p.shopkeeper_id')
                                                                    ->where('p.id', $productId)
                                                                    ->where('sk.resort_id', $this->resort_id)
                                                                    ->select(
                                                                        'p.id', 'p.name', 'p.price', 'p.currency_type',
                                                                        'sk.id as shopkeeper_id', 'sk.name as shopkeeper_name'
                                                                    )
                                                                    ->first();

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 200);
            }

            return response()->json([
                'success'                                   =>  true,
                'message'                                   =>  'Product details fetched successfully.',
                'product_data'                               =>  $product,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function consentRequestview($consentRequestId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $consentRequestId                               =   base64_decode($consentRequestId);
            $employeeId                                     =   $this->user->GetEmployee->id;

            // The explicit column select here previously omitted
            // purchased_date entirely, which is why the consent popup showed
            // "N/A" for Date of Purchase even though the dashboard endpoint
            // (a plain unrestricted select) had it all along. Also missing
            // currency — same fix as employeeDashboard's list above.
            $pendingConsentview                             =   Payment::with(['shopKeeper:id,name','product:id,name,currency_type'])
                                                                    ->select('id', 'shopkeeper_id', 'quantity', 'price', 'status', 'product_id', 'purchased_date')
                                                                    ->where('emp_id', $employeeId)
                                                                    ->where('id',$consentRequestId)
                                                                    ->first();
            if ($pendingConsentview) {
                $pendingConsentview->currency = $pendingConsentview->product->currency_type ?? 'USD';
            }

            $response['status']                             =   true;
            $response['message']                            =   "Pending consents data retrieved successfully.";
            $response['pending_consent_view']               =   $pendingConsentview;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function consentRequestHandle(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'consent_request_id'                            =>  'required',
            'status'                                        =>  'required|in:Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $consentRequestId                                     =   $request->consent_request_id;

        try {

            $pendingConsentview                             =   Payment::find($consentRequestId);
            if (!$pendingConsentview) {
                return response()->json([
                    'success'                               =>  false,
                    'message'                               =>  'Consent request not found.'
                ], 200);
            }

            if ($request->status == 'Approved') {
                $request->status = 'Consented';
            }

            if ($request->status == 'Rejected') {
                $request->status = 'Rejected';
            }

            $pendingConsentview->status                     =   $request->status;
            $pendingConsentview->save();

            // Employee accept/reject never notified the shopkeeper at all —
            // same resort_notifications pattern Payroll\ShopkeeperController::
            // bulkUpdatePaymentStatus() already uses for the opposite
            // direction (HR marking a payment Paid).
            $employeeName = optional(optional($pendingConsentview->employee)->resortAdmin)->first_name ?? 'The employee';
            $verb = $pendingConsentview->status === 'Consented' ? 'accepted' : 'rejected';
            DB::table('resort_notifications')->insert([
                'resort_id'  => $this->resort_id,
                'user_id'    => $pendingConsentview->shopkeeper_id,
                'module'     => 'Staff Shop',
                'type'       => $pendingConsentview->status === 'Consented' ? 'Consent Accepted' : 'Consent Rejected',
                'message'    => $employeeName . ' ' . $verb . ' the consent request for order ' . $pendingConsentview->order_id . '.',
                'status'     => 'unread',
                'request_id' => $pendingConsentview->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response['status']                             =   true;
            $response['message']                            =   "Pending consents Approved successfully.";

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function consentRequestHistory()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $startOfMonth                                   =   Carbon::now()->startOfMonth();
            $endOfMonth                                     =   Carbon::now()->endOfMonth();
            $employeeId                                     =   $this->user->GetEmployee->id;
            // Same fix as employeeDashboard(): consentRequestHandle() flips
            // status on approve/reject, it doesn't touch purchased_date, so
            // "this month" has to be measured by updated_at (when the
            // consent decision happened), not purchased_date (when the item
            // was bought) — a purchase from a prior month that gets
            // consented this month was otherwise invisible here. Also
            // selecting product.currency_type so history rows carry a
            // currency like the dashboard's pending list does.
            $consentHistoryList                             =   Payment::with(['shopKeeper:id,name','product:id,name,currency_type'])
                                                                    ->where('emp_id', $employeeId)
                                                                    ->whereIn('status', ['Consented', 'Rejected'])
                                                                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                                                                    ->orderBy('updated_at', 'DESC')
                                                                    ->get()
                                                                    ->map(function ($payment) {
                                                                        $payment->currency = $payment->product->currency_type ?? 'USD';
                                                                        return $payment;
                                                                    });

            $response['status']                             =   true;
            $response['message']                            =   "Pending consents retrieved successfully.";
            $response['pending_consent_list']               =   $consentHistoryList;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
