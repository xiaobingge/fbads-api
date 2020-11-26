<?php

namespace App\Http\Controllers;


use App\Models\AdAccount;
use App\Models\AdAd;
use App\Models\AdAuth;
use App\Models\AdCampaign;
use App\Models\AdPage;
use App\Models\AdPixel;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function list()
    {
        $result = AdAccount::with('auth')->paginate(10);

        if (env('APP_DEBUG')) {
            return view('list')->with('result', $result);
        } else {
            $html = '';
            foreach ($result->items() as $account) {
                $html .= $account->app_id . ' - ' . $account->id . '<br />';
            }
            $html .= '<a href="' . route('facebook_login') . '">添加新的账号授权<a/>';
            return $html;
        }
    }


    public function login()
    {
        try {
            $accessTokenHelper = \FacebookSdk::getAccessToken();
            if ($accessTokenHelper instanceof AccessToken) {
                // The OAuth 2.0 client handler helps us manage access tokens
                // $oAuth2Client = \FacebookSdk::getOAuth2Client();

                $accessToken = $accessTokenHelper->getValue();
                // var_dump($accessTokenHelper->isLongLived());
//                    if (!$accessTokenHelper->isLongLived()) {
//
//                        // Exchanges a short-lived access token for a long-lived one
//                        try {
//                            $accessTokenHelper = $oAuth2Client->getLongLivedAccessToken($accessToken);
//                            print_r($accessTokenHelper); exit();
//                            $accessToken = $accessTokenHelper->getValue();
//                        } catch (\Facebook\Exception\SDKException $e) {
//                            echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
//                            exit;
//                        }
//                    }

            }
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (!empty($accessToken)) {
            echo "You are logged in!";
            try {
                // Returns a `Facebook\Response` object
                $response = \FacebookSdk::get('/me?fields=id,name,email,accounts,adaccounts,business_users,businesses,permissions,picture', $accessToken);

                $user = $response->getGraphUser();

                $appId = \FacebookSdk::getAppId();

                // $adaccounts = [];
                foreach($user['adaccounts'] as $adaccount) {
                    // echo $adaccount['account_id'] . '-' . $adaccount['id'] . '<br />';
                    \App\Models\AdAccount::firstOrCreate(
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

                $permissions = [];
                foreach ($user['permissions'] as $permission) {
                    $permissions[] = $permission['permission'];
                }

                \App\Models\AdAuth::updateOrCreate(
                    ['type' => 1, 'app_id' => $appId, 'user_id' => $user['id']],
                    [
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'scope' => implode(',', $permissions),
                        'avatar' => isset($user['picture']['data']['url']) ? $user['picture']['data']['url'] : '',
                        'access_token' => $accessToken
                    ]
                );

                return redirect(route('facebook_list'));
                // echo '<a href="' . route('facebook_list') . '">授权列表</a>';
            } catch(FacebookResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
            } catch(FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }

        } else {
            $permissions = [
                'email',
                'pages_manage_ads',  // /page/ads_posts https://developers.facebook.com/docs/graph-api/reference/page/ads_posts/
                'pages_show_list',   // /user/accounts https://developers.facebook.com/docs/graph-api/reference/user/accounts
                'ads_management',
                'ads_read',
                'business_management',
                'public_profile',
                'pages_read_engagement',
                'pages_read_user_content',
                'pages_manage_metadata'
            ];
            $loginUrl = \FacebookSdk::getLoginUrl($permissions, 'facebook_login'); //    $helper->getLoginUrl(Redirect::route('facebook_login'), $permissions);
            return redirect($loginUrl);// echo '<a href="' . $loginUrl . '">Log in with Facebook</a>';
        }
    }

    public function me()
    {
        $user_id = '111581780701071';
        $info = AdAuth::where('user_id', $user_id)->first();
        $accessToken = $info->access_token;

        try {
            $response = \FacebookSdk::get('/me?fields=id,name,email,accounts,adaccounts,business_users,businesses,permissions,picture', $accessToken);
        } catch(FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $user = $response->getGraphUser();

        echo 'adaccounts<br />';
        foreach($user['adaccounts'] as $adaccount) {
            echo $adaccount['account_id'] . '-' . $adaccount['id'] . '<br />';
        }

        // page
        if (isset($user['accounts']) && count($user['accounts']) > 0) {
            foreach ($user['accounts'] as $ad_page) {
                echo $ad_page['id'] . '-' . $ad_page['name']  . '-' . $ad_page['tasks'] . '<br />';
            }
        }

        echo 'permissions<br />';
        foreach ($user['permissions'] as $permission) {
            echo $permission['permission'] . '<br />';
        }

        return 'Email: ' . $user['email'] . '<br />ID: ' . $user['id'] . '<br />Name: ' . $user['name'];
    }

    public function accounts()
    {
        $accessToken = $this->getAccessToken();

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/111581780701071/accounts',
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());
    }

    public function adspixels(Request $request)
    {
        $act_account = $request->get('account');

        if (empty($act_account)) {
            abort(404, '参数错误');
        }

//        $this->getFaceBookClient();
        $account_id = 'act_' . $act_account;

        $accessToken = $this->getAccessToken($act_account);

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            /**
             * @var \Facebook\FacebookResponse $response
             */
            $response = \FacebookSdk::get(
                '/'.$account_id.'/adspixels?fields=id%2Cname%2Ccode',
                $accessToken
            );
            $result = $response->getDecodedBody();
            if (isset($result['data'])) {
                foreach ($result['data'] as $pixel) {
                    AdPixel::updateOrCreate(
                        [
                            'pixel_id' => $pixel['id'],
                            'account_id' => $act_account,
                        ],
                        [
                            'name' => $pixel['name'],
                            'code' => $pixel['code']
                        ]
                    );

                    echo 'Id: ' . $pixel['id'] . ' - Name: '. $pixel['name'] . '<br />';
                }
            }

        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
    }

    public function campaigns(Request $request)
    {
        $act_account = $request->get('account');

        if (empty($act_account)) {
            abort(404, '参数错误');
        }

//        $this->getFaceBookClient();
        $account_id = 'act_' . $act_account;

        $accessToken = $this->getAccessToken($act_account);

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/campaigns?effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective',
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());

//        try {
//            $cursor = (new \FacebookAds\Object\AdAccount($account_id))->getCampaigns();
//
//            if ($cursor instanceof Cursor) {
//                print_r($cursor->getLastResponse()->getContent());
//            } else if ($cursor instanceof \FacebookAds\Http\Response) {
//                print_r($cursor->getContent());
//            } else if ($cursor instanceof \FacebookAds\Object\AbstractCrudObject) {
//                print_r($cursor->exportData());
//            }
//
//            // Loop over objects
//            foreach ($cursor as $campaign) {
//                echo $campaign->{CampaignFields::NAME} . PHP_EOL;
//            }
//        } catch (\Exception $e) {
//            print_r($e->getMessage());
//        }
    }

    public function create_campaign()
    {
        $account_id = 'act_1074776236311593';

        $accessToken = $this->getAccessToken();

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::post(
                '/'.$account_id.'/campaigns',
                [
                    'name' => 'My campaign',
                    'objective' => 'LINK_CLICKS',
                    'status' => 'PAUSED',
                    'special_ad_categories' => '[]',
                ],
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());
    }

    public function adsets(Request $request)
    {
//        $this->getFaceBookClient();
        //$account_id = 'act_1074776236311593';
        //$campaign_id = 23846206403900607;

        //$accessToken = $this->getAccessToken();

        $act_account = $request->get('account');

        if (empty($act_account)) {
            abort(404, '参数错误');
        }

//        $this->getFaceBookClient();
        $account_id = 'act_' . $act_account;

        $accessToken = $this->getAccessToken($act_account);

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/adsets',
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());

    }

    public function create_adset()
    {
        $account_id = 'act_1074776236311593';
        $campaign_id = 23846206403900607;
        $accessToken = $this->getAccessToken();

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::post(
                '/'.$account_id.'/adsets',
                [
                    'name' => 'My First AdSet',
                    'lifetime_budget' => '20000',
                    'start_time' => '2020-11-23T23:15:26-0800',
                    'end_time' => '2020-11-30T23:15:26-0800',
                    'campaign_id' => $campaign_id,
                    'bid_amount' => '500',
                    'billing_event' => 'IMPRESSIONS',
                    'optimization_goal' => 'POST_ENGAGEMENT',
                    'targeting' => '{"age_min":20,"age_max":24,"behaviors":[{"id":6002714895372,"name":"All travelers"}],"genders":[1],"geo_locations":{"countries":["US"],"regions":[{"key":"4081"}],"cities":[{"key":"777934","radius":10,"distance_unit":"mile"}]},"life_events":[{"id":6002714398172,"name":"Newlywed (1 year)"}],"facebook_positions":["feed"],"publisher_platforms":["facebook","audience_network"]}',
                    'status' => 'PAUSED',
                ],
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());
    }


    public function insights_account(Request $request)
    {
        $act_account = $request->get('account');

        if (empty($act_account)) {
            abort(404, '参数错误');
        }

        $account_id = 'act_' . $act_account;
        $accessToken = $this->getAccessToken($act_account);

        $where = [

            //'date_preset' => 'last_30d',
            'default_summary' => true,
            'time_increment' => 1,
            'time_range' => json_encode([
                'since' => '2020-11-20',
                'until' => '2020-11-25'
            ]),

            'level' => 'account',
            'fields' => implode(',', [
                'account_currency','account_id','account_name','action_values','actions','ad_id','ad_name','adset_id','adset_name','buying_type','campaign_id','campaign_name','canvas_avg_view_percent','canvas_avg_view_time','clicks','conversion_rate_ranking','conversion_values','conversions','converted_product_quantity','converted_product_value','cost_per_action_type','cost_per_conversion','cost_per_estimated_ad_recallers','cost_per_inline_link_click','cost_per_inline_post_engagement','cost_per_outbound_click','cost_per_thruplay','cost_per_unique_action_type','cost_per_unique_click','cost_per_unique_inline_link_click','cost_per_unique_outbound_click','cpc','cpm','cpp','ctr','date_start','date_stop','engagement_rate_ranking','estimated_ad_recall_rate','estimated_ad_recallers','frequency','full_view_impressions','full_view_reach','impressions','inline_link_click_ctr','inline_link_clicks','inline_post_engagement','objective','qualifying_question_qualify_answer_rate','quality_ranking','reach','social_spend','spend','unique_clicks','unique_ctr','unique_inline_link_click_ctr','unique_inline_link_clicks','unique_link_clicks_ctr'
            ]),
        ];

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            /**
             * @var \Facebook\FacebookResponse $response
             */
            $response = \FacebookSdk::get(
                '/'.$account_id.'/insights' . (empty($where) ? '' : '?' . http_build_query($where)),
                $accessToken
            );

        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());

    }

    public function insights_campaign(Request $request)
    {
        $campaign = $request->get('campaign');

        if (empty($campaign)) {
            abort(404, '参数错误');
        }
        $info = AdCampaign::with('accountAndAuth')->where('campaign_id', $campaign)->first();
        if (empty($info)) {
            abort(404, '数据不存在');
        }

        print_r($info->campaign_id);
        print_r($info->accountAndAuth->auth->access_token);

        $where = [

            'date_preset' => 'last_7d',
            'time_increment' => 1,
            //'default_summary' => true, // 区间总计
//            'time_ranges' => json_encode([
//                [
//                    'since' => '2020-09-24',
//                    'until' => '2020-10-23'
//                ]
//            ]),
            'level' => 'campaign',
            'fields' => implode(',', [
                'account_currency','account_id','account_name','action_values','actions','buying_type','campaign_id','campaign_name','canvas_avg_view_percent','canvas_avg_view_time','clicks','conversion_rate_ranking','conversion_values','conversions','converted_product_quantity','converted_product_value','cost_per_action_type','cost_per_conversion','cost_per_estimated_ad_recallers','cost_per_inline_link_click','cost_per_inline_post_engagement','cost_per_outbound_click','cost_per_thruplay','cost_per_unique_action_type','cost_per_unique_click','cost_per_unique_inline_link_click','cost_per_unique_outbound_click','cpc','cpm','cpp','ctr','date_start','date_stop','engagement_rate_ranking','estimated_ad_recall_rate','estimated_ad_recallers','frequency','full_view_impressions','full_view_reach','impressions','inline_link_click_ctr','inline_link_clicks','inline_post_engagement','objective','qualifying_question_qualify_answer_rate','quality_ranking','reach','social_spend','spend','unique_clicks','unique_ctr','unique_inline_link_click_ctr','unique_inline_link_clicks','unique_link_clicks_ctr'
            ]),
        ];

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            /**
             * @var \Facebook\FacebookResponse $response
             */
            $response = \FacebookSdk::get(
                '/'.$info->campaign_id.'/insights' . (empty($where) ? '' : '?' . http_build_query($where)),
                $info->accountAndAuth->auth->access_token
            );

        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());

    }

    // 受众
    public function get_customaudiences()
    {
        $account_id = 'act_1074776236311593';

        $accessToken = $this->getAccessToken();

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/customaudiences',
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());
    }

    public function create_customaudiences()
    {
//        $this->getFaceBookClient();
        $account_id = 'act_1074776236311593';

        $accessToken = $this->getAccessToken();

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::post(
                '/'.$account_id.'/customaudiences',
                [
                    'name' => 'My new Custom Audience',
                    'subtype' => 'CUSTOM',
                    'description' => 'People who purchased on my website',
                    'customer_file_source' => 'USER_PROVIDED_ONLY',
                ],
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());

//        try {
//            $cursor = (new \FacebookAds\Object\AdAccount($account_id))->getCampaigns();
//
//            if ($cursor instanceof Cursor) {
//                print_r($cursor->getLastResponse()->getContent());
//            } else if ($cursor instanceof \FacebookAds\Http\Response) {
//                print_r($cursor->getContent());
//            } else if ($cursor instanceof \FacebookAds\Object\AbstractCrudObject) {
//                print_r($cursor->exportData());
//            }
//
//            // Loop over objects
//            foreach ($cursor as $campaign) {
//                echo $campaign->{CampaignFields::NAME} . PHP_EOL;
//            }
//        } catch (\Exception $e) {
//            print_r($e->getMessage());
//        }
    }




    public function ads_archive()
    {
        $this->getFaceBookClient();
        $account_id = 'act_1074776236311593';

    }


    public function ads(Request $request)
    {
        $act_account = $request->get('account');

        if (empty($act_account)) {
            abort(404, '参数错误');
        }

//        $this->getFaceBookClient();
        $account_id = 'act_' . $act_account;

        $accessToken = $this->getAccessToken($act_account);

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/ads',
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());
    }


    // 放弃..
    public function create_ad(Request $request)
    {

        $act_account = $request->get('account');

        if (empty($act_account)) {
            abort(404, '参数错误');
        }

//        $this->getFaceBookClient();
        $account_id = 'act_' . $act_account;

        $accessToken = $this->getAccessToken($act_account);

        $adset_id = '23846206445800607';
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            $creative = [
                'creative' => [
                    'name' => 'ad creative 1',
                    'object_story_spec' => [
                        'page_id' => 103611704908895,
                        'link_data' => [
                            'message' => 'ad creative 1 message',
                            'link' => 'https://www.github.com/lzh1104'
                        ]
                    ]
                ]
            ];


            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::post(
                '/'.$account_id.'/ads',
                [
                    'name' => 'My Ad',
                    'adset_id' => $adset_id,
                    'creative' => json_encode($creative),
                    'status' => 'PAUSED',
                ],
                $accessToken
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        // $graphNode = $response->getGraphNode();
        /* handle the result */
        print_r($response->getDecodedBody());
    }


    public function test151()
    {

        $info = AdCampaign::with('accountAndAuth')->where('id', 3)->first();
        print_r($info);

        //return view('facebook.login');
    }





    private function getAccessToken($act_account)
    {
        $result = AdAccount::with('auth')->where('ad_account_int', $act_account)->first();
        if (!empty($result) && !empty($result->auth->access_token)) {
            return $result->auth->access_token;
        }

        abort(404, '账号不存在');
    }


}
