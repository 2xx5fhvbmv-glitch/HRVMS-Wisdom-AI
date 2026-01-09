<?php

namespace App\Http\Controllers\Resorts\Payroll;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Earnings;
use App\Models\Deduction;
use App\Models\PayrollConfig;
use App\Models\PublicHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ParentAttendace;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\Payment;
use App\Models\ResortSiteSettings;
use App\Models\Payroll;
use App\Models\PayrollDeduction;
use App\Models\PayrollEmployees;
use App\Models\PayrollReview;
use App\Models\PayrollServiceCharge;
use App\Models\PayrollTimeAndAttendance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Events\ResortNotificationEvent;
use Auth;
use Config;
use DB;
use Common;

class PaymentConsentController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index(Request $request,$employee_id)
    {
        $page_title ='Payment Consent Request';
        // dd($employee_id);
        $resort_id = $this->resort->resort_id;
        $employee_id = $employee_id;
        if ($request->ajax()) {
            $query = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('products as p', 'p.id', '=', 'payments.product_id')
                ->join('shopkeepers as s', 's.id', '=', 'payments.shopkeeper_id')
                ->where('payments.emp_id', base64_decode($employee_id))
                ->select([
                    'payments.id',
                    'payments.order_id',
                    'ra.first_name',
                    'ra.last_name',
                    'payments.purchased_date',
                    'payments.quantity',
                    'payments.price',
                    'payments.status',
                    'payments.created_at',
                    'e.Emp_id',
                    's.name as shopkeeper_name',
                ])
                ->orderBy('payments.created_at', 'DESC');
                    // dd($query->get());
            return datatables()->of($query)
                ->addColumn('status', function ($row) {
                    $statusClasses = [
                        'Paid' => 'badge-success',
                        'Partial Paid' => 'badge-info',
                        'Pending Consent' => 'badge-warning',
                        'Consented' => 'badge-theme',
                    ];
                    $class = $statusClasses[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $class . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($payment) {
                    if( $payment->status == 'Pending Consent') {
                        return '<a href="#" class="btn btn-theme btn-sm consent-payment" data-id="'.base64_encode($payment->id).'">Confirm</a>';
                    }
                    else{
                        return '<a href="#" class="btn btn-theme btn-sm consent-payment disabled" data-id="'.base64_encode($payment->id).'">Confirm</a>';
                    }
                })
               ->escapeColumns([])
                ->make(true);
        }

       
        return view('resorts.payroll.payment.index',compact('page_title','employee_id'));
    }

    public function confirmPurchased(Request $request)
    {
        $id = $request->input('paymentID');
        // dd($id);
        // Find the payment by ID or fail
        $payment = Payment::findOrFail(base64_decode($id));

        // Update the status
        $payment->status = "Consented";
        $payment->save();

        // dd($request->all());
        $shopkeeperId = $payment->shopkeeper_id;
        $emp_id = $payment->emp_id;
        // $sender = $this->resort->getEmployee->id;

        $employee = Employee::with(['resortAdmin', 'position'])->where('id', $emp_id )->first();
       
        // Build a notification message
        $names = $employee->resortAdmin->first_name." ".$employee->resortAdmin->last_name;
       
        $notificationMessage = "Payment conscent confirmed/approved by $names !";
        // dd($notificationMessage );

        // Notify all employees about the birthdays
        $type = config('settings.Notifications');
        // dd($type);
        $event = event(new ResortNotificationEvent(Common::nofitication($shopkeeperId, $type[9], $notificationMessage,  $payment)));

        if($event)
        {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment Conscented successfully',
            ]);
        }   

    }
}