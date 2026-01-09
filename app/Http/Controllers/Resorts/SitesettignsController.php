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
        // dd($settings);

        return response()->json([
            'usd_to_mvr' => $settings->DollertoMVR ?? 15.42,  // Default if not set
            'mvr_to_usd' => $settings->MVRtoDoller ?? 0.065
        ]);
    }

    public function UpdateSiteSettings(Request $request)
    {



        $signature_img = $request->signature_img;
        $footer_img = $request->footer_img;
        $resort_id = $request->resort_id;
        $currency = $request->currency;

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'image|mimes:jpeg,png,jpg,gif',
            // 'MVRtoDoller' => 'required|numeric|string|min:2|max:7', // Ensures MVRtoDoller is numeric and has 2 to 7 characters
            // 'DollertoMVR' => 'required|numeric|string|min:2|max:7', // Ensures DollertoMVR is numeric and has 2 to 7 characters
            'footer_img' => 'image|mimes:jpeg,png,jpg,gif',
            'currency' => 'required',
        ], [
            'profile_picture.mimes' => 'The profile picture must be a file of type: jpeg, png, jpg, gif.',
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
                        $collection=['currency' => $currency,
                                        "MVRtoDoller"=>$request->MVRtoDoller,
                                        "DollertoMVR"=>$request->DollertoMVR,
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

                    if (isset($request->profile_picture)) {

                        $fileName = "brand_logo." . $request->profile_picture->getClientOriginalExtension();
                        Common::uploadFile($request->profile_picture, $fileName, config('settings.brand_logo_folder'));
                        $resort->logo = $fileName;

                        
                    }

                    $resort->save();

                    // if($request->hasFile('header_img'))
                    // {
                    //         $header_img = $request->file('header_img');

                    //         $newhimg = $header_img->getClientOriginalName();

                    //         $header_img->move($Path, $newhimg);
                    //         $collection['header_img']= $newhimg;
                    // }


                    if($request->hasFile('signature_img'))
                    {
                            $signature_img = $request->file('signature_img');
                            $newsimg = $signature_img->getClientOriginalName();
                            $signature_img->move($Path, $newsimg);
                            $collection['signature_img']= $newsimg;
                    }


                    if($request->hasFile('footer_img'))
                    {
                            $footer_img = $request->file('footer_img');
                            $newfimg = $footer_img->getClientOriginalName();
                            $footer_img->move($Path, $newfimg);
                            $collection['footer_img']= $newfimg;
                    }





                    ResortSiteSettings::updateOrCreate(
                        ['resort_id' => $resort_id], // Correct key-value array for the condition
                        $collection // The data to be updated or created
                    );
                $response['success'] = true;

                $response['msg'] ="Site settings Updated successfully";
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
            $resort = Auth::guard('resort-admin')->user()->resort;

            $query = ResortNotification::select([
                    'id', 'module', 'type', 'message', 'status', 'created_at'
                ])
                ->where('resort_id', $resort->id);

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
