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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;
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
        return view('shopkeeper.payments.index',compact('page_title','shopkeeper'));
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
            }
            $tableData = $tableData->orderBy('payments.updated_at', 'DESC')
                ->select([
                    'payments.*',
                    'ra.first_name',
                    'ra.last_name',
                    'e.Emp_id',
                    'p.name as product_name',
                    'p.currency_type as product_currency_type',
                    'ra.profile_picture'
                ])
                ->get();

            return datatables()->of($tableData)
                ->addColumn('currency_type', function ($row) {
                    $ct = $row->product_currency_type ?? 'USD';
                    return $ct === 'MVR' ? 'MVR' : 'Dollar';
                })
                ->addColumn('qr_code', function ($row) {
                    if ($row->status === 'Pending Consent' && !empty($row->qr_code)) {
                        return '<button type="button" class="btn btn-sm btn-outline-secondary p-1 payment-qr-icon" data-payment-id="' . (int) $row->id . '" title="View QR Code"><i class="fa-solid fa-qrcode fa-lg"></i></button>';
                    }
                    return '—';
                })
                ->addColumn('name', function ($row) {
                    $profile_pic = Common::getResortUserPicture($row->profile_picture);
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
                        'Consented' => 'badge-themeSkyblueLight',
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
            'profile_picture' => Common::getResortUserPicture($employee->Emp_id)
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
            // Create payment record with the base64 QR code
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

            // Send consent notification (optional)
            if($payment) {
                // $payment->sendConsentProductPurchaseNotification($payment, $shopkeeper);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully',
                'redirect_url' => route('shopkeeper.dashboard'),
                'order_id' => $payment->order_id,
                'qr_code_base64' => $request->qr_code,  // Optionally return the base64 QR code to frontend
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

        $qr = $payment->qr_code;
        if (is_string($qr) && strpos($qr, 'data:') === 0) {
            $qr = preg_replace('#^data:image/\w+;base64,#i', '', $qr);
            $qr = base64_decode($qr);
        }
        if (empty($qr)) {
            abort(404);
        }

        return response($qr, 200, ['Content-Type' => 'image/png']);
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

        $payment = Payment::findOrFail($request->paymentID);
        $shopkeeper = $this->shopkeeper;

        // Send consent notification
        if ($payment) {
            $payment->sendConsentProductPurchaseNotification($payment, $shopkeeper);
            return response()->json(['success' => true, 'message' => 'Consent sent successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
    }

}
