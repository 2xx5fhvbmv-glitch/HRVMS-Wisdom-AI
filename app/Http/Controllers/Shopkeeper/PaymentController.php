<?php
namespace App\Http\Controllers\Shopkeeper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Events\ResortNotificationEvent;

use DB;
use BrowserDetect;
use Route;
use File;
use Str;
use Illuminate\Support\Facades\Session;
use App\Helpers\Common;
use App\Models\Shopkeeper;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ResortNotification;
use App\Models\Payroll;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class PaymentController extends Controller
{
    public $shopkeeper;
    public function __construct()
    {
        $this->shopkeeper = Auth::guard('shopkeeper')->user();
        // dd($this->shopkeeper);
    }

    public function index()
    {
        $page_title ='Payments';
        $shopkeeper = $this->shopkeeper;

        // The AJAX list() endpoint already defaults to the resort's real
        // payroll period when no explicit range is sent — but this page's
        // date-range picker was hardcoded to the calendar month
        // (moment().startOf('month')/endOf('month')) and always sends
        // that explicit range, overriding list()'s correct default. Pass
        // the real period through so the picker opens on the same cycle
        // the Dashboard page already shows.
        $currentPayroll = Payroll::where('resort_id', $shopkeeper->resort_id)
            ->orderBy('start_date', 'desc')
            ->first();
        $payrollStartDate = $currentPayroll->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $payrollEndDate = $currentPayroll->end_date ?? now()->endOfMonth()->format('Y-m-d');

        return view('shopkeeper.payments.index',compact('page_title','shopkeeper','payrollStartDate','payrollEndDate'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            // dd($request->all());
            $searchTerm = $request->searchTerm;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $shopkeeper_id = $this->shopkeeper->id;

            $tableData = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('products as p', 'p.id', '=', 'payments.product_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('payroll_employees')
                        ->whereColumn('payroll_employees.employee_id', 'e.id');
                })
                ->where('payments.shopkeeper_id', $shopkeeper_id)
                ->whereIn('payments.status', ['Paid', 'Partial Paid', 'Pending', 'Pending Consent', 'Consented', 'Rejected']);

            // Fix: Apply search filter correctly
            if ($searchTerm) {
                $tableData->where(function ($query) use ($searchTerm) {
                    $query->where('p.price', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('p.name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('payments.quantity', 'LIKE', "%{$searchTerm}%") // Product Quantity
                          ->orWhere('ra.first_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('ra.last_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('payments.status', 'LIKE', "%{$searchTerm}%");
                });
            }

            // **Date Range Filter**
            if (!empty($startDate) && !empty($endDate)) {
                $tableData->whereBetween('payments.purchased_date', [$startDate , $endDate ]);
            } else {
                // No explicit range picked yet — default to the resort's
                // current payroll period instead of every purchase ever
                // made (same fix as the Dashboard transactions table).
                $currentPayroll = Payroll::where('resort_id', $this->shopkeeper->resort_id)
                    ->orderBy('start_date', 'desc')
                    ->first();
                if ($currentPayroll) {
                    $tableData->whereBetween('payments.purchased_date', [$currentPayroll->start_date, $currentPayroll->end_date]);
                }
            }
            $tableData = $tableData->orderBy('payments.updated_at', 'DESC')
                ->select([
                    'payments.*',
                    'ra.first_name',
                    'ra.last_name',
                    'e.Emp_id',
                    'e.Admin_Parent_id',
                    'p.name as product_name',
                    'p.currency_type as product_currency_type',
                ])
                ->get();

            return datatables()->of($tableData)
                ->addColumn('currency_type', function ($row) {
                    $ct = $row->product_currency_type ?? 'USD';
                    return $ct === 'MVR' ? 'MVR' : 'Dollar';
                })
                ->addColumn('qr_code', function ($row) {
                    $showQr = in_array($row->status, ['Pending Consent', 'Rejected']) && !empty($row->qr_code);
                    if ($showQr) {
                        return '<button type="button" class="btn btn-sm btn-outline-secondary p-1 payment-qr-icon" data-payment-id="' . (int) $row->id . '" title="View QR Code"><i class="fa-solid fa-qrcode fa-lg"></i></button>';
                    }
                    return '—';
                })
                ->addColumn('name', function ($row) {
                    // Same fix as getEmpDetails() — getResortUserPicture()
                    // looks up by resort_admins.id, not a raw profile_picture
                    // filename (always failed the lookup, invisible/broken
                    // photo every row).
                    $profile_pic = Common::getResortUserPicture($row->Admin_Parent_id);
                    if ($row->first_name && $row->last_name) {
                        return '<div class="tableUser-block">
                                    <div class="img-circle">
                                        <img src="' . $profile_pic . '" alt="user">
                                    </div>
                                    <span>' . $row->first_name . ' ' . $row->last_name . '</span>
                                </div>';
                    }
                })
                ->addColumn('product', function ($row) {
                    return $row->product_name;
                })
                ->addColumn('status', function ($row) {
                    $statusClasses = [
                        'Paid' => 'badge-success',
                        'Partial Paid' => 'badge-info',
                        'Pending Consent' => 'badge-warning',
                        // badge-themeSkyblueLight doesn't exist anywhere in
                        // the CSS — the badge fell through to the base
                        // Bootstrap badge's white text with no background
                        // override, invisible on the page background.
                        // badge-theme (dark text, light background) is what
                        // DashboardController's equivalent status column
                        // already correctly uses for "Consented".
                        'Consented' => 'badge-theme',
                        'Rejected' => 'badge-danger',
                    ];
                    $class = $statusClasses[$row->status] ?? 'badge-secondary';
                    $label = $row->status ?: '—';
                    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    switch ($row->status) {
                        case 'Pending Consent':
                            return '<button class="btn btn-warning btn-sm resend-consent" data-id="'.$row->id.'">Send Consent</button>';
                        case 'Consented':
                            // Approved by employee on app — no Deduct Now; deduction handled in payroll
                            return '<span class="text-muted">Consented</span>';
                        case 'Partial Paid':
                            return '<button class="btn btn-info btn-sm continue-deduction" data-id="'.$row->id.'">Continue Deduction</button>';
                        case 'Paid':
                            return '<button class="btn btn-success btn-sm" disabled>Paid</button>';
                        case 'Rejected':
                            return '<button class="btn btn-warning btn-sm resend-consent" data-id="'.$row->id.'">Resend Consent</button>';
                        default:
                            return '<button class="btn btn-secondary btn-sm" disabled>Unknown</button>';
                    }
                })
                ->escapeColumns([])
                ->make(true);
        }

    }

    public function add()
    {
        $page_title ='Add Payments';
        $shopkeeper = $this->shopkeeper;
        // dd($shopkeeper);
        $resort_id = $this->shopkeeper->resort_id;
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->get();
        $products = Product::where('shopkeeper_id',$shopkeeper->id)->get();
        return view('shopkeeper.payments.add',compact('page_title','shopkeeper','employees','products'));
    }

    public function getEmpDetails($id)
    {
        $employee = Employee::with('resortAdmin')->find($id);

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        return response()->json([
            'id' => $employee->id,
            'emp_id' => $employee->Emp_id,
            'name' => $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
            // getResortUserPicture() looks up by resort_admins.id, not the
            // Emp_id code (e.g. "DR-1") — passing Emp_id always failed the
            // ResortAdmin::find() lookup and silently fell back to the
            // default picture for every employee.
            'profile_picture' => Common::getResortUserPicture($employee->Admin_Parent_id)
        ]);
    }

    public function store(Request $request)
    {
        $shopkeeper = $this->shopkeeper;
        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required',
            'qr_code' => ['required', 'regex:/^data:image\/(png|jpeg|jpg);base64,/',], // Validate QR code as base64 string
        ]);

        // Generate unique Order ID
        $order_id = 'ORD-' . strtoupper(Str::random(8));

        try {
            // Create payment record. The client-submitted qr_code is only a
            // live preview generated before the row (and its id) existed —
            // it encodes the raw form fields as JSON, not a lookup key. The
            // mobile scan flow (consent-request-view/{id}) expects the QR
            // to decode to this row's own numeric id, so it's regenerated
            // server-side right after the id is known and overwrites the
            // preview value below.
            $payment = Payment::create([
                'shopkeeper_id' => $shopkeeper->id,
                'order_id' => $order_id,
                'emp_id' => $request->emp_id,
                'purchased_date' => now(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity, // Fixed typo from qunatity
                'price' => $request->price,
                'status' => 'Pending Consent',
                'qr_code' => $request->qr_code,  // Store QR code base64 string
            ]);

            // 'png' needs the imagick extension, which isn't installed on
            // this server (only GD is) — 'svg' renders identically as a
            // scannable QR once displayed in an <img> tag or printed, with
            // no extra extension dependency.
            //
            // Was encoding a bare base64 number (e.g. "ODI=") with no URL
            // or scheme at all — nothing for a phone's camera/scanner to
            // act on, which is why scanning it did nothing/appeared to
            // freeze. A real https:// URL always does *something*
            // sensible on scan (opens a browser at worst); if the mobile
            // app registers Android App Links / iOS Universal Links for
            // this path, scanning opens the app directly to the consent
            // screen instead. No web page exists at this path yet — that
            // and the app-side link registration are follow-up work, not
            // built here.
            $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode(
                QrCode::format('svg')->size(256)->generate(
                    url('shop/consent-request/' . base64_encode((string) $payment->id))
                )
            );
            $payment->qr_code = $qrCodeBase64;
            $payment->save();

            // Send consent notification (optional)
            if($payment) {
                // $payment->sendConsentProductPurchaseNotification($payment, $shopkeeper);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully',
                'redirect_url' => route('shopkeeper.dashboard'),
                'order_id' => $payment->order_id,
                'qr_code_base64' => $qrCodeBase64,  // Optionally return the base64 QR code to frontend
            ]);
        } catch (\Exception $e) {
            // Handle error gracefully
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function qrImage($id)
    {
        $payment = Payment::where('id', $id)
            ->where('shopkeeper_id', $this->shopkeeper->id)
            ->first();

        if (!$payment || empty($payment->qr_code)) {
            abort(404);
        }

        $qr          = $payment->qr_code;
        $contentType = 'image/png';
        if (is_string($qr) && preg_match('#^data:(image/[\w.+-]+);base64,#i', $qr, $m)) {
            $contentType = $m[1];
            $qr          = base64_decode(substr($qr, strlen($m[0])));
        }
        if (empty($qr)) {
            abort(404);
        }

        return response($qr, 200, ['Content-Type' => $contentType]);
    }

    public function getProductPrice(Request $request)
    {
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        return response()->json([
            'success' => true,
            'price' => $product->price,
            'currency_type' => $product->currency_type ?? 'USD',
            'currency_label' => ($product->currency_type ?? 'USD') === 'MVR' ? 'MVR' : 'Dollar'
        ]);
    }

    public function downloadPayments(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $searchTerm = $request->query('search_term');

        $export = new PaymentsExport($month, $year, $startDate, $endDate, $searchTerm);
        $filename = 'payments-' . date('Y-m-d-His') . '.xlsx';

        return Excel::download($export, $filename);
    }

    public function sendConsent(Request $request)
    {
        $request->validate([
            'paymentID' => 'required|exists:payments,id',
        ]);

        $payment = Payment::with('shopKeeper', 'product')
            ->where('shopkeeper_id', $this->shopkeeper->id)
            ->findOrFail($request->paymentID);
        $shopkeeper = $this->shopkeeper;

        $resortId = $payment->shopKeeper->resort_id ?? null;
        if (!$resortId) {
            return response()->json(['success' => false, 'message' => 'Resort not found for this payment.'], 404);
        }

        $productName = $payment->product->name ?? 'Product';
        $title = 'Payment consent request';
        $message = "Please approve your purchase consent from {$shopkeeper->name}. Order #{$payment->order_id} – {$productName}.";

        try {
            // Create in-app notification for mobile (employee sees it in notification list)
            ResortNotification::create([
                'type' => $title,
                'user_id' => $payment->emp_id,
                'module' => 'Payment Consent',
                'resort_id' => $resortId,
                'message' => $message,
                'request_id' => $payment->id,
            ]);

            // Was in-app only — never actually pushed to the phone, so the
            // employee only saw it after reopening the app.
            $employee = Employee::find($payment->emp_id);
            if ($employee && !empty($employee->device_token)) {
                Common::sendPushNotificationForMobile([$employee->device_token], $title, $message, 'Payment Consent', null, null, null, null);
            }
        } catch (\Throwable $e) {
            \Log::error('Shopkeeper sendConsent failed: ' . $e->getMessage(), ['payment_id' => $payment->id]);
            return response()->json(['success' => false, 'message' => 'Failed to send consent notification.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Consent notification sent to mobile app']);
    }

}
