<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Broadcast;

Route::post('/broadcasting/auth', function () {
    \Log::info('AUTH DEBUG', [
        'resort_admin' => Auth::guard('resort-admin')->user(),
        'default' => Auth::user(),
        'session' => session()->all(),
    ]);
    return Broadcast::auth(request());
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


    Route::prefix('touchscreen')->namespace('API')->group(function () {

        Route::get('/', function () {
            return Redirect::to('touchscreen/inspection');
        });

        /// truck casing inspection
        Route::get('/inspection', 'PurchaseCasingController@index')->name('inspection');
        Route::get('/truck-inspection', 'PurchaseCasingController@truckInspection')->name('truck_inspection');
        Route::get('/create-new-purchase-load', 'PurchaseCasingController@createNewPurchaseLoad')->name('create_new_purchase_load');
        Route::get('/get-existing-purchase-load', 'PurchaseCasingController@getExistingPurchaseLoad')->name('get_existing_purchase_load');
        Route::get('/size', 'PurchaseCasingController@getSizeSelectionScreen')->name('get_size_selection_screen');
        Route::get('/purchase', 'PurchaseCasingController@openPurchaseLoadScreen')->name('open_purchase_load_screen');
        Route::get('/brand', 'CasingMakeController@getBrandSelectionScreen')->name('get_brand_selection_screen');
        Route::get('/grade', 'PurchaseCasingController@getGreadSelectionScreen')->name('get_grade_screen');
        Route::get('/patterns', 'CasingPatternController@getPatternSelectionScreen')->name('get_pattern_screen');
        Route::get('/country', 'PurchaseCasingController@getCountrySelectionScreen')->name('get_country_screen');
        Route::get('/final_screen', 'PurchaseCasingController@finalScreen')->name('get_final_screen');
        Route::get('/review_trailer', 'PurchaseCasingController@openReviewLoadPage')->name('open_review_trailer_page');
        Route::get('/serial_number', 'CasingMakeController@openSerialNumberPage')->name('open_serial_number_page');
        Route::get('/original_brand', 'CasingMakeController@getOriginalBrandScreen')->name('get_original_brand_screen');
        Route::get('/original_pattern', 'CasingPatternController@getOriginalPatternScreen')->name('get_original_pattern_screen');
        Route::get('/exchange_serial_number', 'CasingMakeController@openExchangeSerialNumberPage')->name('open_exchange_serial_number_page');
        /// end

        Route::get('/earth-mover-inspection', 'EarthMoverCasingController@index')->name('earth_mover_inspection');
        Route::get('/find-record', 'EarthMoverCasingController@openSerialNumberPage')->name('find_record');
        Route::get('/earth-mover-size', 'EarthMoverCasingController@getSizeSelectionScreen')->name('earth_mover_size');
        Route::get('/earth-mover-brand', 'EarthMoverCasingController@getBrandSelectionScreen')->name('earth_mover_brand');
        Route::get('/earth-mover-grade', 'EarthMoverCasingController@getGreadSelectionScreen')->name('earth_mover_grade');
        Route::get('/earth-mover-final-screen', 'EarthMoverCasingController@finalScreen')->name('earth_mover_final_screen');
        Route::get('/earth-mover-inspector', 'EarthMoverCasingController@getInspector')->name('earth_mover_inspector');
        Route::get('/earth-mover-create-new-purchase-load', 'EarthMoverCasingController@createNewPurchaseLoad')->name('earth_mover_create_new_purchase_load');
        Route::get('/earth-mover-existing-purchase-load', 'EarthMoverCasingController@getExistingPurchaseLoad')->name('earth_mover_existing_purchase_load');
        Route::get('/earth-mover-purchase', 'EarthMoverCasingController@openPurchaseLoadScreen')->name('earth_mover_open_purchase_load_screen');
        Route::get('/earth-mover-review-trailer', 'EarthMoverCasingController@openReviewLoadPage')->name('earth_mover_open_review_trailer_page');


        /// Mapping Process
        //Route::get('/process/{route}/{task}', 'MappingProcessController@index')->name('mapping_process');
        Route::get('/process/{task}', 'MappingProcessController@index')->name('mapping_process');


    });
    
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

