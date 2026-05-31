<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\ResortSiteSettings;
use App\Models\Resort;
use App\Helpers\Common;
use App\Models\ResortAdmin;

use App\Models\ResortNotification;
class SitesettignsController extends Controller
{
    public function index()
    {
        $resort = Auth::guard('resort-admin')->user()->resort;


        $Resort = Resort::where( 'id', $resort->id)->first();

        
        $SiteSettings = ResortSiteSettings::where( "resort_id",$resort->id)->first();

        return view('resorts.sitesttings.index',compact('resort','SiteSettings','Resort'));
    }

    public function getCurrencyRates($resortId)
    {
        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

        // Per the FX-rate developer reference (May 2026):
        //   - Canonical rate: 1 USD = 15.42 MVR (DollertoMVR).
        //   - MVR → USD is ALWAYS derived by division. Never store / read
        //     a separate MVRtoDoller column — multiplying by a stored
        //     inverse like 0.06484 introduces rounding drift
        //     (7710 × 0.06484 = $499.92 instead of $500.00).
        $dollarToMvr = (float) ($settings->DollertoMVR ?? 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;

        return response()->json([
            'usd_to_mvr' => $dollarToMvr,           // multiply USD by this
            'mvr_to_usd' => 1.0 / $dollarToMvr,     // divide MVR by DollertoMVR (== multiply by this)
        ]);
    }

    public function UpdateSiteSettings(Request $request)
    {



        $signature_img = $request->signature_img;
        $footer_img = $request->footer_img;
        $resort_id = $request->resort_id;
        $currency = $request->currency;

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'mimes:jpeg,png,jpg,gif,svg,webp,heic,heif',
            // 'MVRtoDoller' => 'required|numeric|string|min:2|max:7', // Ensures MVRtoDoller is numeric and has 2 to 7 characters
            // 'DollertoMVR' => 'required|numeric|string|min:2|max:7', // Ensures DollertoMVR is numeric and has 2 to 7 characters
            'footer_img' => 'mimes:jpeg,png,jpg,gif,svg,webp,heic,heif',
            'currency' => 'required',
        ], [
            'profile_picture.mimes' => 'The profile picture must be a file of type: jpeg, png, jpg, gif, svg, webp, heic, heif.',
            'MVRtoDoller.numeric' => 'The MVR to Dollar conversion must be a numeric value.',
            // 'MVRtoDoller.min' => 'The MVR to Dollar conversion must be at least :min characters.',
            // 'MVRtoDoller.max' => 'The MVR to Dollar conversion may not be greater than :max characters.',
            'DollertoMVR.numeric' => 'The Dollar to MVR conversion must be a numeric value.',
            // 'DollertoMVR.min' => 'The Dollar to MVR conversion must be at least :min characters.',
            // 'DollertoMVR.max' => 'The Dollar to MVR conversion may not be greater than :max characters.',
        ]);

        // Check if validation fails

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }



           try
           {
                        // FX-rate policy (May 2026 dev reference):
                        //   - DollertoMVR is the only stored rate. Locked to
                        //     15.42 (system-wide canonical rate); a submitted
                        //     value is honoured if it parses as a positive
                        //     number, otherwise it falls back to 15.42.
                        //   - MVRtoDoller is deprecated. We overwrite it with
                        //     the derived inverse so any legacy reader still
                        //     gets the right number until those callers are
                        //     migrated to deriving via division themselves.
                        $dollarToMvr = is_numeric($request->DollertoMVR) && (float) $request->DollertoMVR > 0
                            ? (float) $request->DollertoMVR
                            : 15.42;
                        $mvrToDollarDerived = 1.0 / $dollarToMvr;

                        $collection=['currency' => $currency,
                                        "MVRtoDoller"=>$mvrToDollarDerived,
                                        "DollertoMVR"=>$dollarToMvr,
                                        // "Doller_img"=>'dollar-sign.svg',
                                        // "MVR_img"=>'maldives-currency-icon.svg',
                                        "Doller_img"=>'doller-currency-icon.svg',
                                        "MVR_img"=>'maldives-currency-icon-new.svg',
                                        'Footer'=>$request->footer,
                                        "FinalApproval"=>$request->FinalApproval

                        ];
                    $resort = Resort::find($resort_id);

                    $resort->address1 = $request->address1;
                    $resort->address2 = $request->address2;
                    $resort->country = $request->country;
                    $resort->state = $request->state;
                    $resort->city = $request->city;
                    $resort->zip  = $request->zip;

                    // Brand logo upload.
                    //
                    // - Detection: use hasFile(), not isset(). isset() is true
                    //   even when the multipart field is present with no actual
                    //   file, and getClientOriginalExtension() then throws.
                    // - Unique filename: previous build wrote "brand_logo.{ext}"
                    //   every time → cached old logo never refreshed. Stamped
                    //   filename gives each upload a unique URL.
                    // - Storage driver: route via STORAGE_DRIVER env so prod
                    //   (where the local public/ folder isn't writable from
                    //   the app server) hits Wasabi instead. Same pattern as
                    //   Common::UploadProfileAwsPic. Without this the upload
                    //   "worked" locally but silently failed on live because
                    //   the file was written to a path the web server never
                    //   served.
                    if ($request->hasFile('profile_picture')) {
                        $logoFile = $request->file('profile_picture');
                        $ext      = $logoFile->getClientOriginalExtension();
                        $fileName = 'brand_logo_' . time() . '.' . $ext;
                        $basePath = config('settings.brand_logo_folder');
                        $driver   = env('STORAGE_DRIVER', 'local');

                        if ($driver === 'local') {
                            Common::uploadFile($logoFile, $fileName, $basePath);
                        } else {
                            // Storage::disk handles directory creation, content
                            // type detection, and bucket-relative paths for
                            // both 'wasabi' and 's3' disks defined in
                            // config/filesystems.php.
                            \Storage::disk($driver)->putFileAs($basePath, $logoFile, $fileName);
                        }
                        $resort->logo = $fileName;
                    }

                    $resort->save();

                    // signature_img / footer_img were previously handled here
                    // but referenced an undefined $Path variable (would 500 the
                    // request the moment either field was sent). The matching
                    // view inputs are commented out, so the dead branches are
                    // removed rather than left as latent landmines. When/if the
                    // inputs come back, wire them through Common::uploadFile
                    // the same way the brand logo above does.





                    ResortSiteSettings::updateOrCreate(
                        ['resort_id' => $resort_id], // Correct key-value array for the condition
                        $collection // The data to be updated or created
                    );

                $response['success'] = true;
                $response['msg']     = 'Site settings Updated successfully';
                // Returning the fresh logo URL so the AJAX success handler
                // can swap the preview <img src> without a full page reload.
                // Already includes the cache-busting ?v=<timestamp> applied
                // by Common::GetResortLogo.
                $response['logo_url'] = Common::GetResortLogo($resort_id);
                return response()->json($response);
            }
                catch(\Exception $e)
                {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            $response['success'] = false;
            $response['msg'] = $e->getMessage();
            return response()->json($response);
            }

    }

    public function UpdateCurrency (Request $request)
    {
            $validator = Validator::make($request->all(), [

                'currency' => 'required',
            ], [
                'currency.required' => 'Please choose a currency.',
            ]);

            if ($validator->fails()) {
             return response()->json( $validator->errors(), 400);
           }

           try{

                $resource =    ResortSiteSettings::where('resort_id' , $request->resortid)->first();

                if(isset($resource))
                {
                    $resource->update(['currency'=>$request->currency] );
                    $response['success'] = true;

                    $response['msg'] ="Resort Currency Updated successfully";
                }
                else
                {


                    $response['success'] = false;

                    $response['msg'] ="Please Add Site settings first";
                }






            }
            catch(\Exception $e)
            {
                \Log::emergency( "File: ".$e->getFile() );
                \Log::emergency( "Line: ".$e->getLine() );
                \Log::emergency( "Message: ".$e->getMessage() );

                $response['success'] = false;
                $response['msg'] = $e->getMessage();
            }
            return response()->json($response);

    }

    public function NotificationMark(Request $request)
    {
        try
        {
            $r = ResortNotification::find($request->id);
            $r->status = 'read';
            $r->save();
            $response['success'] = true;
            $response['msg'] ="Mark As Read successfully";
            return response()->json($response);
        }
        catch(\Exception $e)
        {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            $response['success'] = false;
            $response['msg'] = $e->getMessage();
        }
        return response()->json($response);

    }
    public function NotificationList(Request $request)
    {
        $page_title = "Notifications";

        if ($request->ajax()) {
            $resort = Auth::guard('resort-admin')->user();
            $employee = $resort->GetEmployee;
            $resortId = $resort->resort_id;

            // Always scope to notifications addressed to the logged-in user, matching
            // the bell-dropdown behavior in Common::ResortNotification(). Broadcasts
            // (e.g. for HR/GM) are inserted as one row per recipient by the senders,
            // so each user only sees their own here too.
            $userEmpId = optional($employee)->id;
            $query = ResortNotification::select([
                    'id', 'module', 'type', 'message', 'status', 'created_at', 'user_id'
                ])
                ->where('resort_id', $resortId)
                ->where('status', '!=', 'deleted')
                ->where('user_id', $userEmpId);

            return datatables()->of($query)
                ->order(function ($query) use ($request) {
                    // Check if DataTables sent order info
                    if ($request->has('order')) {
                        $columns = $request->input('columns');
                        $order = $request->input('order')[0];
                        $columnIndex = $order['column'];
                        $columnName = $columns[$columnIndex]['data'];
                        $direction = $order['dir'];

                        // Apply the sorting
                        $query->orderBy($columnName, $direction);
                    } else {
                        // Default sorting
                        $query->orderBy('created_at', 'desc');
                    }
                })
                ->editColumn('status', function ($row) {
                    return $row->status == 'read' ? 'Read' : 'Unread';
                })
                ->addColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['Action'])
                ->make(true);
        }

        return view('resorts.sitesttings.NotificationList', compact('page_title'));
    }
    public function NotificationDestory($id)
    {
        $id = base64_decode($id);
        try
        {
            ResortNotification::where("id",$id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Notification Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
         
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Notification'], 500);
        }
    }

    public function UpdateResortMenuType(Request $request){

            $resort = Auth::guard('resort-admin')->user();

            if($resort){
                $resort->update([
                    'menu_type' => $request->menuType
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Menu type '.$resort->menu_type.' updated successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Resort not found.'
                ], 404);
            }
        
    }
}
