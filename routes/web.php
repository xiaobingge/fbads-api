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
    return view('welcome');
});



Route::any('/facebook/login', 'IndexController@login')->name('facebook_login');

Route::any('/facebook/me', 'IndexController@me');
Route::any('/facebook/adaccounts', 'IndexController@adaccounts');

Route::any('/facebook/accounts', 'IndexController@accounts');

Route::any('/facebook/campaigns', 'IndexController@campaigns');
Route::any('/facebook/ads', 'IndexController@ads');

Route::any('/facebook/test151', 'IndexController@test151');