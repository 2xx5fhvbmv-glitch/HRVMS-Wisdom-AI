<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\ResortSiteSettings;
use App\Models\Settings;
use App\Models\Admin;
use App\Helpers\Common;
use DB;
use File;
class SettingsController extends Controller
{
  public function updateSettings()
  {

    try {
      $data = Settings::first();
      $dateFormats = Common::getDateFormats();
      $timeFormats = Common::getTimeFormats();

      return view('admin.settings.update')->with(
        compact(
          'data',
          'dateFormats',
          'timeFormats',
        )
      );
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function saveSettings(Request $request)
  {
    try {
      $settings = Settings::first();
      // dd($request);

      $input = $request->except(['_token','external_path', 'stripe_secret']);

      // dd($input);

      if( isset( $request->stripe_secret ) && $request->stripe_secret != '' ) {
        $input['stripe_secret'] = $request->stripe_secret;
      }

      $path_logo = config('settings.site_logo_folder');
      $path_favicon = config('settings.site_favicon_folder');
      $path_watermark = config('settings.watermark_folder');

      if( isset( $request->site_logo ) ) {
        if( $settings->site_logo != '' ) {
          $imgPath = $path_logo."/".$settings->site_logo;

          if( \File::exists( $imgPath ) ) {
            unlink($imgPath);
          }
        }

        $fileName = "logo.".$request->site_logo->getClientOriginalExtension();
        Common::uploadFile($request->site_logo, $fileName, $path_logo);
        $input['site_logo'] = $fileName;
      }

      if( isset( $request->footer_logo ) ) {
        if( $settings->footer_logo != '' ) {
          $imgPath = $path_logo."/".$settings->footer_logo;

          if( \File::exists( $imgPath ) ) {
            unlink($imgPath);
          }
        }

        $fileName = "footer_logo.".$request->footer_logo->getClientOriginalExtension();
        Common::uploadFile($request->footer_logo, $fileName, $path_logo);
        $input['footer_logo'] = $fileName;
      }

      if( isset( $request->admin_logo ) ) {
        if( $settings->admin_logo != '' ) {
          $imgPath = $path_logo."/".$settings->admin_logo;

          if( \File::exists( $imgPath ) ) {
            unlink($imgPath);
          }
        }

        $fileName = "admin_logo.".$request->admin_logo->getClientOriginalExtension();
        Common::uploadFile($request->admin_logo, $fileName, $path_logo);
        $input['admin_logo'] = $fileName;
      }

      if( isset( $request->header_logo ) ) {
        if( $settings->header_logo != '' ) {
          $imgPath = $path_logo."/".$settings->header_logo;

          if( \File::exists( $imgPath ) ) {
            unlink($imgPath);
          }
        }

        $fileName = "header_logo.".$request->header_logo->getClientOriginalExtension();
        Common::uploadFile($request->header_logo, $fileName, $path_logo);
        $input['header_logo'] = $fileName;
      }

      if( isset( $request->site_favicon)) {
        if( $settings->site_favicon != '' ) {
          $imgPath = $path_favicon."/".$settings->site_favicon;

          if( \File::exists( $imgPath ) ) {
            unlink($imgPath);
          }
        }
        $fileName = "favicon.".$request->site_favicon->getClientOriginalExtension();
        Common::uploadFile($request->site_favicon, $fileName, $path_favicon);
        $input['site_favicon'] = $fileName;
      }

      $input['site_title'] = $request->site_title;
      // dd($input);

      $settings->update($input);

      $response['success'] = true;
      $response['msg'] = __('messages.updateSuccess', [ 'name' => 'Settings' ]);
      return response()->json($response);
    } catch (\Exception $e) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      return response()->json($response);
    }
  }
}
