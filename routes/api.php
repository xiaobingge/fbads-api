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

\Laravel\Passport\Passport::$ignoreCsrfToken = true;
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('login', 'ApiController@login');
Route::post('register', 'ApiController@register');
Route::get('click', 'ClickController@index');

Route::any('adv/index','Admin\OverViewController@index');
Route::get('adv/account','Admin\OverViewController@getAdAccount');


Route::group(['middleware' => 'auth.jwt'], function () {
    Route::get('logout', 'ApiController@logout');
    Route::get('user', 'ApiController@getAuthUser');

});

//需要验证用户登录的路由
Route::group(['middleware' => ['cors','multiauth:api']], function () {
    Route::get('/facebook/user_list', 'Admin\FacebookController@user_list');
    Route::get('/facebook/refresh_account', 'Admin\FacebookController@refresh_account');
    Route::get('/facebook/adaccounts', 'Admin\FacebookController@adaccounts');
    Route::get('/facebook/pixel', 'Admin\FacebookController@adspixels');
    Route::get('/facebook/page', 'Admin\FacebookController@facebookPage');


    Route::get('/goods/index', 'Admin\GoodsController@index');
    Route::get('/goods/detail', 'Admin\GoodsController@detail');
    Route::get('/goods/image', 'Admin\GoodsController@goods_image');
    Route::get('/goods/sku', 'Admin\GoodsController@goods_sku');
    Route::get('/goods/option', 'Admin\GoodsController@goods_option');

    Route::get('/goods/fail_hash', 'Admin\GoodsController@fail_hash');
    Route::post('/goods/fail_push', 'Admin\GoodsController@fail_push');

});


Route::group([], function () {
	Route::get('getShopifyCollectCount', 'FaceApiController@getShopifyCollectCount');
	Route::get('getShopifyCollectList', 'FaceApiController@getShopifyCollectList');
	Route::get('getShopifyGoodCount', 'FaceApiController@getShopifyGoodCount');
	Route::get('getShopifyGoodList', 'FaceApiController@getShopifyGoodList');

});

