<?php
use Illuminate\Http\Request;
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

//Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

//上传处理
Route::group(['middleware' => ['cors']], function () {
    Route::post('/uploadFiles', function(\App\Services\UploadService $service){
        return $service->uploadFiles();
    });
});

//工具

Route::post('/tools', function(\App\Services\ToolsService $service){
    return $service->index();
});

//H5页面地址
Route::get('page/index', 'H5Controller@index');


Route::any('wechat/index', 'WeChatController@index');
//微信授权
Route::group(['middleware' => ['web', 'wechat.oauth']], function () {
    Route::get('wechat/auth', 'WeChatController@authLogin');
});

Route::any('/facebook/login', 'IndexController@login')->name('facebook_login');
Route::any('/facebook/list', 'IndexController@list')->name('facebook_list');

// 取消授权回调网址
Route::any('/facebook/leave', 'IndexController@leave')->name('facebook_leave');

// 数据删除请求网址
Route::any('/facebook/remove', 'IndexController@remove')->name('facebook_remove');

Route::any('/facebook/deletion', 'IndexController@deletion')->name('facebook_deletion');

if (env('APP_DEBUG')) {
    Route::any('/facebook/test151', 'IndexController@test151');
    Route::any('/facebook/me', 'IndexController@me');
    Route::any('/facebook/accounts', 'IndexController@accounts');

    Route::any('/facebook/get_customaudiences', 'IndexController@get_customaudiences');
    Route::any('/facebook/create_customaudiences', 'IndexController@create_customaudiences');


    Route::any('/facebook/campaigns', 'IndexController@campaigns');
    Route::any('/facebook/create_campaign', 'IndexController@create_campaign');

    Route::any('/facebook/adsets', 'IndexController@adsets');
    Route::any('/facebook/create_adset', 'IndexController@create_adset');

    Route::any('/facebook/insights_account', 'IndexController@insights_account');
    Route::any('/facebook/insights_campaign', 'IndexController@insights_campaign');


    Route::any('/facebook/ads', 'IndexController@ads');
    Route::any('/facebook/create_ad', 'IndexController@create_ad');
    Route::any('/facebook/adspixels', 'IndexController@adspixels');
}


Route::any('/shopify/tool', 'HomeController@tool');
Route::post('/shopify/tool_ajax', 'HomeController@tool_ajax');


//后台管理系统路由
Route::any('admin/loginCenter', 'Admin\LoginController@login');
Route::group(['namespace' => 'Admin'], function () {
    Route::group(['middleware' => ['api', 'multiauth:admin']], function () {
        Route::any('admin/user', 'UserController@user');
        Route::any('admin/menu', 'UserController@menu');
        Route::any('user/updatePassword', 'UserController@updatePassword');
        Route::group(['middleware' => ['permission']], function () {

            //菜单管理
            Route::get('menu/index', 'MenuController@index');
            Route::post('menu/create', 'MenuController@create');
            Route::post('menu/update', 'MenuController@update');
            Route::get('menu/delete', 'MenuController@delete');

            //角色管理
            Route::get('role/index', 'RoleController@index');
            Route::post('role/create', 'RoleController@create');
            Route::post('role/update', 'RoleController@update');
            Route::get('role/delete', 'RoleController@delete');
            Route::get('role/permission', 'RoleController@getPermission');
            Route::post('role/setPermission', 'RoleController@setPermission');
            Route::get('role/getUsers', 'RoleController@getUsers');
            Route::post('role/bindUsers', 'RoleController@bindUsers');

            //后台用户
            Route::get('user/index', 'UserController@index');
            Route::get('user/getRoles', 'UserController@getRoles');
            Route::post('user/create', 'UserController@create');
            Route::post('user/update', 'UserController@update');
            Route::get('user/updateStatus', 'UserController@updateStatus');
            Route::get('user/permission', 'UserController@getPermission');
            Route::post('user/setPermission', 'UserController@setPermission');
			Route::get('user/delete', 'UserController@delete');

            //自定义菜单
            Route::get('wechat/getmenus', 'WechatController@getMenus');
            Route::post('wechat/setmenus', 'WechatController@setMenus');
            Route::get('wechat/getmaterial', 'WechatController@getMaterial');
//            Route::any('wechat/setmaterial', 'WechatController@setMaterial');
            Route::post('wechat/sysmaterial', 'WechatController@sysMaterial');
            Route::get('wechat/selectmaterial', 'WechatController@selectMaterial');

            //自动回复
            Route::get('reply/getReply', 'ReplyController@getReply');
            Route::get('reply/getReplyDetail', 'ReplyController@getReplyDetail');
            Route::post('reply/handleReply', 'ReplyController@handleReply');
            Route::get('reply/deleteReply', 'ReplyController@deleteReply');
            Route::get('reply/getRules', 'ReplyController@getRules');
            Route::post('reply/handleRule', 'ReplyController@handleRule');
            Route::get('reply/deleteRule', 'ReplyController@deleteRule');


        });
    });
});




