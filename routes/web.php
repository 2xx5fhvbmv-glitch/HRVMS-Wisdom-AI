<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Broadcast;

// Broadcast::auth() resolves the channel-authorization user via the
// request's DEFAULT guard ($request->user()) — resort-admin logins never
// touch the 'web' guard at all, so every private/presence channel
// subscription from the web portal (Chat Module, etc.) was silently
// failing auth here. shouldUse() makes 'resort-admin' the default guard
// for the rest of this request, matching how the resort-admin session
// middleware group already behaves elsewhere.
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    if (Auth::guard('resort-admin')->check()) {
        Auth::shouldUse('resort-admin');
    }
    return Broadcast::auth($request);
});
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Performance Meeting Accept/Decline (public - no auth required, accessed from email)
Route::get('/meeting/respond/{token}', 'Resorts\Performance\PerformanceMeetingController@showMeetingResponse')->name('meeting.respond');
Route::post('/meeting/respond/{token}', 'Resorts\Performance\PerformanceMeetingController@submitMeetingResponse')->name('meeting.respond.submit');

Route::get('/migrate', function () {
    // Only allow from a secure IP or with auth!
 Artisan::call('migrate', [
        '--force' => true, // required to bypass confirmation in web
    ]);    return 'database migrate ok';
});
Route::get('/qb', function () {
  $pages= App\Models\ModulePages::where('module_id', 17)->delete();
  return 'success';
});

Route::get('/qb', function () {
  $pages= App\Models\ModulePages::where('module_id', 17)->delete();
  return 'success';
});

Route::get('/', function () {
    return Redirect::to('/resort');
});



Route::get('marquee', function(){
    echo  File::get('C:\Users\Spaculus\Downloads\krishika.txt');
});

Route::group(['middleware' => ['web']], function () {


    // Touchscreen routes commented out - not part of HRMS
    // Route::prefix('touchscreen')->namespace('API')->group(function () {
    //     ...
    // });
    
});

Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    return 'clear ok';
  });
  

  Route::get('migrate/rollback',function() {
    Artisan::call('migrate:rollback');
    return 'database migrate rollback ok';
  });
  
  Route::get('/survey-change-status', function () {
    Artisan::call('links:survey-change-status');
    return 'survey change status ok';
  });


  Route::get('/onboarding-new-emp-hire-notification', function () {
    Artisan::call('links:onboarding-new-emp-hire-notification');
    return 'onboarding new emp hire notification ok';
  });


  Route::get('/calendar-push-notification', function () {
    Artisan::call('links:calendar-push-notification');
    return 'calendar push notification ok';
  });

