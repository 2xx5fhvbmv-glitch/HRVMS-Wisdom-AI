<?php

Route::get('shopkeeper/payment/confirm-purchased/{id}', ['App\Http\Controllers\Shopkeeper\PaymentController','confirmPurchased'])->name('dashboard.payment.confirm');

/** Routes without login ***/
Route::prefix('shopkeeper')->namespace('Shopkeeper')->group(function () {
    Route::get('/',  ['App\Http\Controllers\Shopkeeper\ShopkeeperLoginController','showLoginForm'])->name('shopkeeper.loginindex');
    Route::post('/do-login', ['App\Http\Controllers\Shopkeeper\ShopkeeperLoginController','login'])->name('shopkeeper.login');
    Route::get('/request-password', ['App\Http\Controllers\Shopkeeper\ForgotPasswordController','requestPassword'])->name('shopkeeper.password.request');
    Route::post('/request-password-submit', ['App\Http\Controllers\Shopkeeper\ForgotPasswordController','requestPasswordSubmit'])->name('shopkeeper.password.request-submit');
    Route::get('/reset-password/{token}', ['App\Http\Controllers\Shopkeeper\ForgotPasswordController','resetPassword'])->name('shopkeeper.password.reset');
    Route::post('/reset-password-submit', ['App\Http\Controllers\Shopkeeper\ForgotPasswordController','resetPasswordSubmit'])->name('shopkeeper.password.reset-submit');
    Route::post('/check-email-exists', ['App\Http\Controllers\Shopkeeper\ForgotPasswordController','checkEmailExists'])->name('shopkeeper.emailExistForgotPassword');
    Route::get('/permission-denied', ['App\Http\Controllers\Shopkeeper\LoginController@','permissionDenied'])->name('shopkeeper.permission.denied');

});

Route::prefix('shopkeeper')->middleware(['auth:shopkeeper','revalidate'])->namespace('Shopkeeper')->group(function () {

    /*** Logout ***/
    Route::get('/logout', 'ShopkeeperLoginController@logout')->name('shopkeeper.logout');
     /*** Dashboard ***/
    Route::get('dashboard', 'DashboardController@index')->name('shopkeeper.dashboard');
    Route::get('profile', 'DashboardController@profile')->name('shopkeeper.profile');
    Route::post( '/UpdateUserProfile', 'DashboardController@UpdateProfile' )->name('shopkeeper.update.profile');
    Route::get('configuration', 'ConfigurationController@index')->name('shopkeeper.configuration');
    //product module 
    Route::post('products/submit', 'ConfigurationController@store')->name('products.submit');
    Route::get('/products', 'ConfigurationController@products')->name('shopkeeper.products');
    Route::get('/products/list', 'ConfigurationController@list')->name('shopkeeper.products.list');
    Route::get('/products/show/{id}', 'ConfigurationController@show')->name('shopkeeper.products.show');
    Route::delete('/products/delete/{id}', 'ConfigurationController@destroy')->name('shopkeeper.products.destroy');
    Route::put('/products/inline-update/{id}', 'ConfigurationController@inlineUpdate')->name('shopkeeper.products.inlineUpdate');
  
    Route::get('/products/export', 'ConfigurationController@exportProducts')->name('shopkeeper.products.download');
    Route::post('/products/Import', 'ConfigurationController@ImportProducts')->name('shopkeeper.products.import');
    Route::post('products/import/preview', 'ConfigurationController@importPreview')->name('shopkeeper.products.import.preview');
    Route::post('products/imports/submit', 'ConfigurationController@submit')->name('shopkeeper.products.import.submit');

    //Payment Module 
    Route::get('payments', 'PaymentController@index')->name('shopkeeper.payment.history');
    Route::get('payments/history', 'PaymentController@list')->name('shopkeeper.payment.list');
    Route::get('payments/list', 'DashboardController@list')->name('dashboard.payment.list');

    Route::get('payments/add', 'PaymentController@add')->name('shopkeeper.payment.add');
    Route::get('employees/get-details/{id}', 'PaymentController@getEmpDetails')->name('employees.details.get');
    Route::post('payments/store', 'PaymentController@store')->name('shopkeeper.payment.store');
    ROute::post('payments/send-consent', 'PaymentController@sendConsent')->name('shopkeeper.payment.sendConsent');
    Route::get('/get-product-price', 'PaymentController@getProductPrice')->name('getProductPrice');

    Route::post('/payments/deduct', 'DashboardController@deductAmount')->name('payments.deduct');

    Route::get('/dashboard/payment/download', 'PaymentController@downloadPayments')->name('dashboard.payment.download');


});