<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdAd;
use App\Models\AdAuth;
use App\Models\AdCampaign;
use App\Models\AdPage;
use App\Models\AdPixel;
use Illuminate\Http\Request;

class FacebookController extends Controller
{
    public function user_list(Request $request)
    {
        $statusText = [
            0 => '正常',
            1 => '不可用',
            2 => '锁定'
        ];

        $result = AdAuth::paginate(10);

        $retList = [];
        foreach ($result->items() as $val)
        {
            $retList[] = [
                'id' => $val->id,
                'user_id'  => $val->user_id,
                'name'  => $val->name,
                'avatarUrl'  => $val->avatar,
                'email'  => $val->email,
                'scope'  => $val->scope,
                'status'  => $statusText[$val->status],
                'createTime'  => $val->last_modified,

            ];
        }

        return response()->json([
            'code' => 1000,
            'msg'  => 'success',
            'data' => [
                'hasMorePage' => $result->hasMorePages() ? 1 : 0,
                'list' => $retList
            ]
        ]);
    }

    public function refresh_account(Request $request)
    {
        $user_id = $request->input('user_id');

        $info = AdAuth::where('user_id', $user_id)->first();
        if (empty($info) || empty($info->access_token)) {
            response()->json([
                'code' => 1001,
                'msg'  => '用户不存在'
            ]);
        }
        $accessToken = $info->access_token;

        // Returns a `Facebook\Response` object
        $response = \FacebookSdk::get('/me?fields=id,name,email,accounts,adaccounts,business_users,businesses,permissions,picture', $accessToken);

        $user = $response->getGraphUser();

        $appId = \FacebookSdk::getAppId();

        // $adaccounts = [];
        foreach($user['adaccounts'] as $adaccount) {
            AdAccount::firstOrCreate(
                [
                    'type' => 1,
                    'app_id' => $appId,
                    'user_id' => $user['id'],
                    'ad_account_int' =>$adaccount['account_id'],
                    'ad_account' => $adaccount['id']
                ]
            );
        }

        // page
        if (isset($user['accounts']) && count($user['accounts']) > 0) {
            foreach ($user['accounts'] as $ad_page) {
                AdPage::updateOrCreate(
                    ['page_id' => $ad_page['id'], 'user_id' => $user['id']],
                    [
                        'access_token' => $ad_page['access_token'],
                        'name' => $ad_page['name'],
                        'tasks' => $ad_page['tasks'],
                        'status' => 1
                    ]
                );
            }
        }

        return response()->json([
            'code' => 1000,
            'msg'  => 'success'
        ]);
    }


    public function adaccounts()
    {
        $result = AdAccount::paginate(10, ['user_id', 'ad_account','ad_account_int','name']);

        return response()->json([
            'code' => 1000,
            'msg'  => 'success',
            'data' => [
                'hasMorePage' => $result->hasMorePages() ? 1 : 0,
                'list' => $result->items()
            ]
        ]);
    }

    public function adspixels(Request $request)
    {
        $result = AdPixel::paginate(10);

        return response()->json($result);
    }

    public function facebookPage(Request $request)
    {
        $result = AdPage::paginate(10, ['id', 'user_id', 'page_id', 'name', 'link', 'is_published']);

        return response()->json([
            'code' => 1000,
            'msg'  => 'success',
            'data' => [
                'hasMorePage' => $result->hasMorePages() ? 1 : 0,
                'list' => $result->items()
            ]
        ]);
    }

    public function campaigns(Request $request)
    {

    }

    public function adsets(Request $request)
    {

    }

    public function ads(Request $request)
    {
    }


}
