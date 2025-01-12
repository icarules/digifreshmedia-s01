<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::group(
    [
        'namespace' => 'Iomsapi\V1',
        'prefix' => 'v1',
    ], function () {
    Route::get('inventory',  ['uses' => 'IomsapiController@getInventory']);

    Route::get('inventory/list',  ['uses' => 'IomsapiController@getInventoryList']);

    Route::get('inventory/{id}', ['uses' => 'IomsapiController@getInventoryItem']);

    Route::get('inventory/{id}/images', ['uses' => 'IomsapiController@getInventoryItemImages']);

    Route::get('clients', ['uses' => 'IomsapiController@getClients']);

    Route::get('clients/{id}', ['uses' => 'IomsapiController@getClient']);

    Route::get('lease', ['uses' => 'IomsapiController@getLease']);
});

Route::group(
    [
        'namespace' => 'Iomsapi\V2',
        'prefix' => 'v2',
    ], function () {
    Route::get('inventory',  ['uses' => 'IomsapiController@getInventory']);

    Route::get('inventory/list',  ['uses' => 'IomsapiController@getInventoryList']);

    Route::get('inventory/{id}', ['uses' => 'IomsapiController@getInventoryItem']);

    Route::get('inventory/{id}/images', ['uses' => 'IomsapiController@getInventoryItemImages']);

    Route::get('clients', ['uses' => 'IomsapiController@getClients']);

    Route::get('clients/{id}', ['uses' => 'IomsapiController@getClient']);

    Route::get('lease', ['uses' => 'IomsapiController@getLease']);
});

Route::group(
    [
        'namespace' => 'Iomsapi\V3',
        'prefix' => 'v3',
    ], function () {
    Route::get('inventory',  ['uses' => 'IomsapiController@getInventory']);

    Route::get('inventory/list',  ['uses' => 'IomsapiController@getInventoryList']);

    Route::get('inventory/{id}', ['uses' => 'IomsapiController@getInventoryItem']);

    Route::get('inventory/{id}/images', ['uses' => 'IomsapiController@getInventoryItemImages']);

    Route::get('clients', ['uses' => 'IomsapiController@getClients']);

    Route::get('clients/{id}', ['uses' => 'IomsapiController@getClient']);

    Route::get('lease', ['uses' => 'IomsapiController@getLease']);
});


Route::group(
    [
        'namespace' => 'Iomsapi\V6',
        'prefix' => 'v6',
    ], function () {
    Route::get('inventory',  ['uses' => 'IomsapiController@getInventory']);

    Route::get('inventory/list',  ['uses' => 'IomsapiController@getInventoryList']);

    Route::get('inventory/{id}', ['uses' => 'IomsapiController@getInventoryItem']);

    Route::get('inventory/{id}/images', ['uses' => 'IomsapiController@getInventoryItemImages']);

    Route::get('clients', ['uses' => 'IomsapiController@getClients']);

    Route::get('clients/{id}', ['uses' => 'IomsapiController@getClient']);

    Route::get('lease', ['uses' => 'IomsapiController@getLease']);
});