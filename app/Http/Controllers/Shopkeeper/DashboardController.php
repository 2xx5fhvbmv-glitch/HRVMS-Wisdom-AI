<?php
namespace App\Http\Controllers\Shopkeeper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use DB;
use BrowserDetect;
use Route;
use File;
use Illuminate\Support\Facades\Session;
use App\Helpers\Common;
use App\Models\Shopkeeper;
use App\Models\Payment;
use App\Models\PayrollConfig;
use App\Models\Payroll;

class DashboardController extends Controller
{
    public $shopkeeper;
    public function __construct()
    {
        $this->shopkeeper = Auth::guard('shopkeeper')->user();
    }

    public function index()
    {
        // dd( $this->shopkeeper->resort_id);
        $page_title ='Shopkeeper Dashboard';
        $shopkeeper = $this->shopkeeper;
        $total_payments = 0;
        $cutoff_day = PayrollConfig::where('resort_id', $this->shopkeeper->resort_id)->value('cutoff_day');
        return view('shopkeeper.dashboard.index',compact('page_title','shopkeeper','total_payments','cutoff_day'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $shopkeeper_id = $this->shopkeeper->id;
            $tableData = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('products as p', 'p.id', '=', 'payments.product_id')
                ->where('payments.shopkeeper_id', $shopkeeper_id)
                ->where(function ($query) {
                    $query->where('payments.status', '!=','Paid')
                          ->orWhere('payments.status', '!=','Pending');
                });
        
            if ($request->filled('month') || $request->filled('year')) {
                if ($request->filled('month')) {
                    $tableData->whereMonth('purchased_date', $request->month);
                }
                if ($request->filled('year')) {
                    $tableData->whereYear('purchased_date', $request->year);
                }
            } else {
                // No explicit filter yet — default to the resort's current
                // payroll period instead of showing every purchase ever
                // made, so the table lands on something meaningful before
                // the user picks a month/year themselves.
                $currentPayroll = Payroll::where('resort_id', $this->shopkeeper->resort_id)
                    ->orderBy('start_date', 'desc')
                    ->first();
                if ($currentPayroll) {
                    $tableData->whereBetween('purchased_date', [$currentPayroll->start_date, $currentPayroll->end_date]);
                }
            }
            
            // updated_at bumps on every status change (Consented, Rejected,
            // deduction, ...), so an old payment whose status just changed
            // jumped above genuinely newer payments that hadn't been
            // touched since creation — created_at is the actual "recent"
            // the user means.
            $tableData = $tableData->orderBy('payments.created_at', 'DESC')
                ->select([
                    'payments.*',
                    'ra.id as admin_id',
                    'ra.first_name',
                    'ra.last_name',
                    'e.Emp_id',
                    'p.name as product_name',
                    'p.currency_type as product_currency_type',
                    'ra.profile_picture',
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
                    // getResortUserPicture() expects the ResortAdmin id, not
                    // the raw stored picture path — it was never resolving
                    // a real photo, only ever the default placeholder.
                    $profile_pic = Common::getResortUserPicture($row->admin_id);
                    if ($row->first_name && $row->last_name) {
                        return '<div class="tableUser-block">
                                    <div class="img-circle">
                                        <img src="' . $profile_pic . '" alt="user">
                                    </div>
                                    <span>' . $row->first_name . ' ' . $row->last_name . '</span>
                                </div>';
                    }
                })
                ->editColumn('purchased_date', function ($row) {
                    return $row->purchased_date ? \Carbon\Carbon::parse($row->purchased_date)->format('d M Y') : '—';
                })
                ->addColumn('product', function ($row) {
                    return $row->product_name;
                })
                ->addColumn('status', function ($row) {
                    $statusClasses = [
                        'Paid' => 'badge-success',
                        'Partial Paid' => 'badge-info',
                        'Pending Consent' => 'badge-warning',
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

    public function profile()
    {
        $page_title ='Profile';
        $profile = Shopkeeper::where('id',$this->shopkeeper->id)->first();
        return view('shopkeeper.dashboard.profile',compact('page_title','profile'));
    }

    public function UpdateProfile(Request $request)
    {
        // dd($request);
        $path_profile_image = config('settings.ShopkeeperProfile_folder');
        DB::beginTransaction();
        try
        {
            $shopkeeper = Shopkeeper::find($request->id);
            $shopkeeper->name = $request->name;
            // Email is intentionally not updatable from this form (also
            // the shopkeeper login identifier) — the field is disabled
            // client-side, but a disabled input isn't submitted at all, so
            // this line used to null the email out on every save regardless.
            $shopkeeper->contact_no = $request->contact_no;
           
            if(isset($request->password))
            {
                $shopkeeper->password = Hash::make($request->password);
            }

            if ($request->file('profile_photo'))
            {
                $fileName = $request->profile_photo->getClientOriginalName();
                Common::uploadFile($request->profile_photo, $fileName, $path_profile_image);
                if (File::exists(public_path($path_profile_image.'/'.$shopkeeper->profile_photo)))
                {
                    File::delete(public_path($path_profile_image.'/'.$shopkeeper->profile_photo));
                }
                $shopkeeper->profile_photo = $fileName;
            }

            $shopkeeper = $shopkeeper->save();
            DB::commit();
            $response['success'] = true;
            $response['html']= '' ;
            $response['msg'] = __('Profile Updated successfully');
            return response()->json($response);

        }
        catch (\Exception $e)
        {
        DB::rollBack();
            $response['success'] = false;
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
            return response()->json($response);
        }

    }

    public function deductAmount(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'paymentID' => 'required|exists:payments,id',
            'deduction_amt'  => 'required|numeric|min:0',
        ]);

        $payment = Payment::where('id', $request->paymentID)
            ->where('shopkeeper_id', $this->shopkeeper->id)
            ->firstOrFail();
        $cutoff_day = PayrollConfig::where('resort_id', $this->shopkeeper->resort_id)->value('cutoff_day');
        $current_day = now()->day; // Get current day (1-31)

        if ($current_day >= $cutoff_day) {
            return response()->json(['error' => 'Manual deduction is not allowed after the cutoff date.'], 403);
        }

        // Deduct amount and update payment status
        $payment->cash_paid  = $request->deduction_amt;
        if ($payment->cash_paid + $payment->payroll_deducted >= $payment->price) {
            $payment->status = 'Paid';
        } else {
            $payment->status = 'Partial Paid';
        }
        $payment->save();

        return response()->json(['success' => 'Cash payment recorded.']);
    }

    /**
     * Get shopkeeper notifications (sidebar AJAX)
     */
    public function getNotifications()
    {
        $shopkeeperId = $this->shopkeeper->id;
        $resortId = $this->shopkeeper->resort_id;

        $notifications = DB::table('resort_notifications')
            ->where('resort_id', $resortId)
            ->where('user_id', $shopkeeperId)
            ->where('module', 'Staff Shop')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'type', 'message', 'status', 'request_id', 'created_at']);

        $html = '';
        if ($notifications->isNotEmpty()) {
            foreach ($notifications as $notif) {
                $timeAgo = \Carbon\Carbon::parse($notif->created_at)->diffForHumans();
                $activeClass = $notif->status === 'unread' ? 'active' : '';
                $html .= '<div class="notification-box ' . $activeClass . ' class_remove_me_' . $notif->id . '">
                    <a href="javascript:void(0);" class="d-flex profile-dropdown">
                        <div class="flex-shrink-0 img-box">
                            <i class="fa-solid fa-shop fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5>' . e($notif->type) . '</h5>
                            <p>' . e($notif->message) . '</p>
                            <br><span>' . $timeAgo . '</span>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="btn-lg-icon btn-light-grey MarkShopNotification" data-id="' . $notif->id . '">
                        <i class="fas fa-envelope-open" aria-hidden="true"></i>
                    </a>
                </div>';
            }
        } else {
            $html = '<div class="notification-box"><p>No Notifications</p></div>';
        }

        return response()->json(['success' => true, 'html' => $html]);
    }

    /**
     * Mark shopkeeper notification as read
     */
    public function markNotification(Request $request)
    {
        DB::table('resort_notifications')
            ->where('id', $request->id)
            ->update(['status' => 'read']);

        return response()->json(['success' => true]);
    }

    /**
     * Notification list page
     */
    public function notificationList()
    {
        $page_title = 'Notifications';
        $shopkeeperId = $this->shopkeeper->id;
        $resortId = $this->shopkeeper->resort_id;

        $notifications = DB::table('resort_notifications')
            ->where('resort_id', $resortId)
            ->where('user_id', $shopkeeperId)
            ->where('module', 'Staff Shop')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('shopkeeper.notifications.index', compact('page_title', 'notifications'));
    }

}