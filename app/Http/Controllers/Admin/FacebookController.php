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

    public function adaccounts()
    {
        $result = AdAccount::paginate(10, ['name']);

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
