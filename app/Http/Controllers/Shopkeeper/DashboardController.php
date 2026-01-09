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
        
            if ($request->has('month') && !empty($request->month)) {
                $tableData->whereMonth('purchased_date', $request->month);
            }
        
            if ($request->has('year') && !empty($request->year)) {
                $tableData->whereYear('purchased_date', $request->year);
            }
            
            $tableData = $tableData->orderBy('payments.updated_at', 'DESC')
                ->select([
                    'payments.*',
                    'ra.first_name',
                    'ra.last_name',
                    'e.Emp_id',
                    'p.name as product_name',
                    'ra.profile_picture',
                ])
                ->get(); 
        
            return datatables()->of($tableData)
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
                        'Consented' => 'badge-theme',
                    ];
                    $class = $statusClasses[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $class . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    switch ($row->status) {
                        case 'Pending Consent':
                            return '<button class="btn btn-warning btn-sm resend-consent" data-id="'.$row->id.'">Send Consent</button>';
                        case 'Consented':
                            return '<button class="btn btn-primary btn-sm deduct-now" data-id="'.$row->id.'">Deduct Now</button>';
                        case 'Partial Paid':
                            return '<button class="btn btn-info btn-sm continue-deduction" data-id="'.$row->id.'">Continue Deduction</button>';
                        case 'Paid':
                            return '<button class="btn btn-success btn-sm" disabled>Paid</button>';
                        case 'Rejected':
                            return '<button class="btn btn-danger btn-sm" disabled>Rejected</button>';
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
            $shopkeeper->email = $request->email;
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

        $payment = Payment::findOrFail($request->paymentID);
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

}