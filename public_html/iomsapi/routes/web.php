<?php

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

Route::get('/', function () {
//    return view('auth.login');
    return redirect()->route('apikey.index');
});

Auth::routes([
    'register' => false
]);

Route::get('/home', 'HomeController@index')->name('home');

Route::get('apikey', [
    'as'   => 'apikey.index',
    'uses' => 'Iomsapi\ApikeyController@index'
]);
Route::get('apikey/generate', [
    'as'   => 'apikey.generate',
    'uses' => 'Iomsapi\ApikeyController@generate'
]);
Route::post('apikey', [
    'as'   => 'apikey.store',
    'uses' => 'Iomsapi\ApikeyController@store'
]);
Route::get('apikey/{id}/edit', [
    'as'   => 'apikey.edit',
    'uses' => 'Iomsapi\ApikeyController@edit'
]);
Route::patch('apikey/{id}', [
    'as'   => 'apikey.update',
    'uses' => 'Iomsapi\ApikeyController@update'
]);
Route::get('apikey/{id}/delete', [
    'as'   => 'apikey.delete',
    'uses' => 'Iomsapi\ApikeyController@delete'
]);
Route::delete('apikey/{id}', [
    'as'   => 'apikey.destroy',
    'uses' => 'Iomsapi\ApikeyController@destroy'
]);
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
    return $exitCode;
});
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return $exitCode;
});
