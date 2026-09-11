<?php

namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shopkeeper;
use App\Models\Payment;
use App\Models\PayrollConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Common;
use App\Exports\ResortShopkeeperPaymentsExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;
use Config;
use DB;

class ShopkeeperController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    /**
     * Whether the current user (HR HOD, HR Excom, Finance HOD, Finance Excom) can change payment status.
     */
    protected function canUpdatePaymentStatus(): bool
    {
        $employee = $this->resort->getEmployee ?? null;
        if (!$employee) {
            return false;
        }
        $rankPosition = Common::getEmployeeRankPosition($employee);
        $position = $rankPosition['position'] ?? null;
        $rank = $rankPosition['rank'] ?? null;
        $allowedPositions = ['HR', 'Finance'];
        $allowedRanks = ['HOD', 'EXCOM'];
        return in_array($position, $allowedPositions) && in_array($rank, $allowedRanks);
    }

    public function index()
    {
        if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Shopkeeper';
        $resort_id = $this->resort->resort_id;
        return view('resorts.payroll.shopkeeper.index',compact('page_title'));
    }

    public function create()
    {
        if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.create')) == false){

            if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.view'))){
                return redirect()->route('shopkeepers.index');
            }else{

                return abort(403, 'Unauthorized access');
            }
        }

        $page_title ='Create Shopkeeper';
        $resort_id = $this->resort->resort_id;
        $recentShopkeepers = Shopkeeper::where('resort_id', $resort_id)
            ->orderBy('updated_at', 'DESC')
            ->limit(5)
            ->get();
        return view('resorts.payroll.shopkeeper.create',compact('page_title','recentShopkeepers'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = $this->resort->resort_id;
            $query = Shopkeeper::where('resort_id', $resort_id)
                ->orderBy('updated_at', 'DESC');

            if ($request->searchTerm && $request->searchTerm != '') {

                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->searchTerm.'%')
                          ->orWhere('email', 'like', '%'.$request->searchTerm.'%')
                          ->orWhere('contact_no', 'like', '%'.$request->searchTerm.'%');
                });
            }
            $tableData = $query->get();

            $edit_class = '';
            $delete_class = '';
            if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($tableData)
            // One combined Action column (View / Edit / Delete icon buttons)
            // instead of the old separate "view_more" text-button column —
            // presentation only, same $row->id driving every link/attribute.
            ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                $id = htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8');
                $viewUrl = e(route('resort.shopkeeper.payments', ['id' => $row->id]));
                $viewBtn = '<a href="' . $viewUrl . '" class="sk-ico view" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></a>';
                $editBtn = $edit_class === 'd-none' ? '' : '<button type="button" class="sk-ico edit edit-row-btn" title="Edit" data-shopkeeper-id="' . $id . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>';
                $delBtn = $delete_class === 'd-none' ? '' : '<button type="button" class="sk-ico del delete-row-btn" title="Delete" data-shopkeeper-id="' . $id . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>';
                return '<div class="sk-acts">' . $viewBtn . $editBtn . $delBtn . '</div>';
            })
            // escapeColumns(['action']) previously meant "escape (break)
            // the action column's HTML" — yajra's default is '*' (escape
            // everything), and listing a column here adds it to the
            // escape set rather than exempting it. escapeColumns([])
            // is the "allow HTML" pattern already used correctly
            // elsewhere in this codebase (e.g. Admin\AdminController).
            ->escapeColumns([])
            ->make(true);
        }
    }

    public function store(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), 
        [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'contact_no' => 'required|string|regex:/^\+?[0-9]{7,15}$/',
        ],
        [
            'name.required' => 'Please enter name',
            'email.required' => 'Please enter email',
             'contact_no.required' => 'Please enter contact no'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $generatedPassword = Str::random(10); // Generate a 10-character random string
        // Hash the password before saving it to the database
        $hashedPassword = Hash::make($generatedPassword);
        $check = Shopkeeper::where('resort_id', $resort_id)
            ->where('email', $request->email)
            ->first();
        if($check) {
            return response()->json(['success' => false, 'msg' => 'Shopkeeper with this email already exists.']);
        }

        $shopkeeper = Shopkeeper::create([
            'resort_id' => $resort_id,
            'name' => $request->name,
            'email' => $request->email,
            'password'=> $hashedPassword,
            'contact_no' => $request->contact_no,
        ]);

        if($shopkeeper)
        {
            $shopkeeper->sendShopkeeperRegistrationEmail($shopkeeper, $generatedPassword);
        }

        return response()->json([
            'success' => true,
            'msg' => 'Shopkeeper Created Successfully and login credentials sent to shopkeeper.',
            'redirect_url' => route('shopkeepers.index')
        ]);
    }

    public function inlineUpdate(Request $request, $id)
    {
        // Find the division by ID
        $shopkeeper = Shopkeeper::where('id', $id)->where('resort_id', $this->resort->resort_id)->first();

        if (!$shopkeeper) {
            return response()->json(['success' => false, 'message' => 'shopkeeper not found.']);
        }

        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'contact_no' => 'required|string|regex:/^\+?[0-9]{7,15}$/',
        ]);

        try {
            // Update the division's attributes
            $shopkeeper->name = $request->input('name');
            $shopkeeper->email = ucwords($request->input('email'));
            $shopkeeper->contact_no = $request->input('contact_no');
            
            // Save the changes
            $shopkeeper->save();

            // Return a JSON response
            return response()->json(['success' => true, 'message' => 'Shopkeeper updated successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
      
            return response()->json(['success' => false, 'message' => 'Failed to update shopkeeper.']);
        }
    }

    public function destroy($id)
    {
        try {
            $shopkeeper = Shopkeeper::where('id', $id)->where('resort_id', $this->resort->resort_id)->firstOrFail();
            $shopkeeper->delete();  // Soft delete if you're using soft deletes, otherwise use forceDelete()

            return response()->json(['success' => true, 'message' => 'Shopkeeper deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete shopkeeper.']);
        }
    }

    public function payments($id)
    {
        $resort_id = $this->resort->resort_id;
        $shopkeeper = Shopkeeper::where('id', $id)->where('resort_id', $resort_id)->firstOrFail();
        $page_title = 'Payments - ' . $shopkeeper->name;
        $canUpdatePaymentStatus = $this->canUpdatePaymentStatus();
        return view('resorts.payroll.shopkeeper.payments', compact('page_title', 'shopkeeper', 'canUpdatePaymentStatus'));
    }

    public function paymentsList(Request $request, $id)
    {
        if (!$request->ajax()) {
            return redirect()->route('shopkeepers.index');
        }
        $resort_id = $this->resort->resort_id;
        $shopkeeper = Shopkeeper::where('id', $id)->where('resort_id', $resort_id)->firstOrFail();

        $searchTerm = $request->searchTerm;
        // Month filter (current year only, per the dropdown) replaces the old
        // date-range picker, which defaulted to the current month and
        // silently hid every payment outside it — e.g. a shopkeeper with
        // 14 real Consented/Paid payments across the last several months
        // only ever saw the 1 that happened to land in the current month.
        $startDate = null;
        $endDate = null;
        if ($request->filled('month')) {
            $monthDate = Carbon::createFromDate(now()->year, (int) $request->month, 1);
            $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d');
        }

        // Only approved consent: Consented, Paid, Partial Paid (exclude Pending Consent, Rejected, Pending)
        $tableData = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('products as p', 'p.id', '=', 'payments.product_id')
            ->where('payments.shopkeeper_id', $shopkeeper->id)
            ->whereIn('payments.status', ['Consented', 'Paid', 'Partial Paid']);

        if ($searchTerm) {
            $tableData->where(function ($query) use ($searchTerm) {
                $query->where('p.price', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('p.name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payments.quantity', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ra.first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ra.last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('payments.status', 'LIKE', "%{$searchTerm}%");
            });
        }
        if (!empty($startDate) && !empty($endDate)) {
            $tableData->whereBetween('payments.purchased_date', [$startDate, $endDate]);
        }

        // payments.price is stored in the product's own currency (MVR or
        // USD) — a blind SUM() mixes the two. Normalize each row to USD
        // before summing so the frontend's formatAmount($total, 'USD') call
        // isn't fed a raw MVR value it then mislabels as USD.
        //
        // "Payable" means still owed — Paid rows are already settled and
        // must not count toward it (the table above still lists them for
        // history; only this total excludes them).
        $usdToMvrRate = Common::getUsdToMvrRate();
        $totalAmount = (clone $tableData)
            ->where('payments.status', '!=', 'Paid')
            ->select('payments.price', 'p.currency_type')
            ->get()
            ->sum(function ($row) use ($usdToMvrRate) {
                return $row->currency_type === 'MVR' ? ($row->price / $usdToMvrRate) : $row->price;
            });

        $tableData = $tableData->orderBy('payments.updated_at', 'DESC')
            ->select([
                'payments.*',
                'ra.first_name',
                'ra.last_name',
                'e.Emp_id',
                'p.name as product_name',
                'p.currency_type as product_currency_type',
                'e.Admin_Parent_id',
            ])
            ->get();

        $canUpdatePaymentStatus = $this->canUpdatePaymentStatus();

        return datatables()->of($tableData)
            ->with('total_amount', $totalAmount)
            ->with('can_update_payment_status', $canUpdatePaymentStatus)
            ->addColumn('checkbox', function ($row) use ($canUpdatePaymentStatus) {
                $canCheck = $canUpdatePaymentStatus && in_array($row->status, ['Consented', 'Partial Paid']);
                if (!$canCheck) {
                    return '—';
                }
                return '<input type="checkbox" class="sk-chk payment-row-checkbox" data-payment-id="' . (int) $row->id . '" aria-label="Select row">';
            })
            ->addColumn('currency_type', function ($row) {
                $ct = $row->product_currency_type ?? 'USD';
                return $ct === 'MVR' ? 'MVR' : 'Dollar';
            })
            ->addColumn('name', function ($row) {
                // getResortUserPicture() looks up by resort_admins.id, not a
                // raw profile_picture filename — that lookup always failed,
                // falling back to the default picture for every employee.
                // Always resolves to at least the app's generic silhouette
                // default, so only treat it as a real photo when it differs
                // from that default — otherwise fall back to initials
                // rather than showing the generic placeholder.
                if (!($row->first_name && $row->last_name)) {
                    return '—';
                }
                $fullName = trim($row->first_name . ' ' . $row->last_name);
                $parts = preg_split('/\s+/', $fullName);
                $initials = strtoupper(($parts[0][0] ?? '') . (isset($parts[1]) ? $parts[1][0] : '')) ?: '?';
                $photo = Common::getResortUserPicture($row->Admin_Parent_id);
                if ($photo === url(config('settings.default_picture'))) {
                    $photo = null;
                }
                $img = $photo ? '<img src="' . e($photo) . '" alt="' . e($fullName) . '" onerror="this.remove()">' : '';
                return '<div class="sk-nm"><span class="sk-av"><span class="sk-av-fallback">' . e($initials) . '</span>' . $img . '</span><span class="sk-t">' . e($fullName) . '</span></div>';
            })
            ->addColumn('product', function ($row) {
                return $row->product_name;
            })
            ->editColumn('purchased_date', function ($row) {
                return $row->purchased_date ? \Carbon\Carbon::parse($row->purchased_date)->format('d M Y') : '—';
            })
            ->addColumn('status', function ($row) {
                // Only Consented/Paid/Partial Paid ever reach this table
                // (the query above already filters to those three), but
                // keep a neutral fallback for any unexpected value rather
                // than assuming the list can never widen.
                $pills = [
                    'Paid' => 'paid',
                    'Consented' => 'consented',
                    'Partial Paid' => 'partial',
                ];
                $pillClass = $pills[$row->status] ?? 'neutral';
                return '<span class="sk-pill ' . $pillClass . '"><span class="sk-dot"></span>' . e($row->status) . '</span>';
            })
            ->escapeColumns([])
            ->make(true);
    }

    public function paymentsExport(Request $request, $id)
    {
        $resort_id = $this->resort->resort_id;
        $shopkeeper = Shopkeeper::where('id', $id)->where('resort_id', $resort_id)->firstOrFail();

        $startDate = null;
        $endDate = null;
        if ($request->filled('month')) {
            $monthDate = Carbon::createFromDate(now()->year, (int) $request->query('month'), 1);
            $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d');
        }
        $searchTerm = $request->query('search_term');

        $export = new ResortShopkeeperPaymentsExport($shopkeeper->id, $startDate, $endDate, $searchTerm);
        $filename = 'shopkeeper-payments-' . $shopkeeper->id . '-' . date('Y-m-d-His') . '.xlsx';

        return Excel::download($export, $filename);
    }

    public function bulkUpdatePaymentStatus(Request $request)
    {
        $request->validate([
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'required|integer|exists:payments,id',
        ]);

        if (!$this->canUpdatePaymentStatus()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only HR/Finance HOD or Excom can update.'], 403);
        }

        $resort_id = $this->resort->resort_id;

        // Get payments before update to send notifications
        $payments = Payment::whereIn('id', $request->payment_ids)
            ->whereHas('shopKeeper', fn ($q) => $q->where('resort_id', $resort_id))
            ->whereIn('status', ['Consented', 'Partial Paid'])
            ->with(['shopKeeper', 'employee.resortAdmin'])
            ->get();

        $updated = Payment::whereIn('id', $request->payment_ids)
            ->whereHas('shopKeeper', fn ($q) => $q->where('resort_id', $resort_id))
            ->whereIn('status', ['Consented', 'Partial Paid'])
            ->update(['status' => 'Paid']);

        // Create notifications for shopkeepers
        foreach ($payments->groupBy('shopkeeper_id') as $shopkeeperId => $shopPayments) {
            $empNames = $shopPayments->map(fn($p) => $p->employee->resortAdmin->first_name ?? 'Employee')->unique()->implode(', ');
            $totalAmount = $shopPayments->sum('price');
            \DB::table('resort_notifications')->insert([
                'resort_id' => $resort_id,
                'user_id' => $shopkeeperId,
                'module' => 'Staff Shop',
                'type' => 'Payment Approved',
                'message' => $shopPayments->count() . ' payment(s) totalling $' . number_format($totalAmount, 2) . ' marked as Paid for ' . $empNames,
                'status' => 'unread',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $updated . ' payment(s) marked as Paid.',
            'updated_count' => $updated,
        ]);
    }
}