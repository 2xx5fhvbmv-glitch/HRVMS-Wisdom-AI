<?php

Route::get('/clear', function () {
  Artisan::call('optimize:clear');
  return 'clear ok';
});

Route::get('/migrate',function() {
  Artisan::call('migrate');
  return 'database migrate ok';
});
Route::get('/migrate/rollback',function() {
  Artisan::call('migrate:rollback');
  return 'database migrate rollback ok';
});
Route::get('/passport/keys',function() {
    Artisan::call('passport:keys');
    return 'passport keys ok';
});
Route::get('/passport/install',function() {
    Artisan::call('passport:install');
    return 'passport install ok';
  });