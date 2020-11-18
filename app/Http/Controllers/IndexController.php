<?php

namespace App\Http\Controllers;


use App\Models\AdAccount;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function list()
    {
        $result = AdAccount::with('auth')->paginate(10);

        return view('list')->with('result', $result);
    }


    public function login()
    {
        try {
            $accessTokenHelper = \FacebookSdk::getAccessToken();
            print_r($accessTokenHelper);
            if ($accessTokenHelper instanceof AccessToken) {
                // The OAuth 2.0 client handler helps us manage access tokens
                // $oAuth2Client = \FacebookSdk::getOAuth2Client();

                $accessToken = $accessTokenHelper->getValue();
                var_dump($accessTokenHelper->isLongLived());
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
                $response = \FacebookSdk::get('/me?fields=id,name,email,accounts,adaccounts,business_users,businesses,permissions', $accessToken);

                $user = $response->getGraphUser();

                // $adaccounts = [];
                foreach($user['adaccounts'] as $adaccount) {
                    // echo $adaccount['account_id'] . '-' . $adaccount['id'] . '<br />';
                    \App\Models\AdAccount::firstOrCreate(
                        [
                            'user_id' => $user['id'],
                            'ad_account_int' =>$adaccount['account_id'],
                            'ad_account' => $adaccount['id']
                        ]
                    );
                }

                $permissions = [];
                foreach ($user['permissions'] as $permission) {
                    $permissions[] = $permission['permission'];
                }

                \App\Models\AdAuth::updateOrCreate(
                    ['type' => 1, 'user_id' => $user['id']],
                    [
                        'name' => $user['name'], 'email' => $user['email'],
                        'scope' => implode(',', $permissions),
                        'access_token' => $accessToken]
                );


                echo '<a href="' . route('facebook_list') . '">授权列表</a>';
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
            echo '<a href="' . $loginUrl . '">Log in with Facebook</a>';
        }
    }

    public function me()
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = \FacebookSdk::get('/me?fields=id,name,email,accounts,adaccounts,business_users,businesses,permissions', $accessToken);
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

        echo 'permissions<br />';
        foreach ($user['permissions'] as $permission) {
            echo $permission['permission'] . '<br />';
        }

        return 'Email: ' . $user['email'] . '<br />ID: ' . $user['id'] . '<br />Name: ' . $user['name'];
    }


    public function adaccounts()
    {
        $this->getFaceBookClient();
        $user_id = '111581780701071';
        $me = new \FacebookAds\Object\User($user_id);
        $cursor = $me->getAdAccounts();

        if ($cursor instanceof Cursor) {
            print_r($cursor->getLastResponse()->getContent());
        } else if ($cursor instanceof \FacebookAds\Http\Response) {
            print_r($cursor->getContent());
        } else if ($cursor instanceof \FacebookAds\Object\AbstractCrudObject) {
            print_r($cursor->exportData());
        }

//
//        $accessToken = $this->getAccessToken();
//
//        $fb = $this->getFbClient();
//        /* PHP SDK v5.0.0 */
//        /* make the API call */
//        try {
//            // Returns a `Facebook\FacebookResponse` object
//            $response = $fb->get(
//                '/111581780701071/adaccounts',
//                $accessToken
//            );
//        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
//            echo 'Graph returned an error: ' . $e->getMessage();
//            exit;
//        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
//            echo 'Facebook SDK returned an error: ' . $e->getMessage();
//            exit;
//        }
//
//        // $graphNode = $response->getGraphNode();
//        /* handle the result */
//        print_r($response->getDecodedBody());
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
    public function create_ad()
    {
        $account_id = 'act_1074776236311593';
        $adset_id = '23846206445800607';
        $accessToken = $this->getAccessToken();

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::post(
                '/'.$account_id.'/ads',
                [
                    'name' => 'My Ad',
                    'adset_id' => $adset_id,
                    'creative' => '{"creative": {\"name\": \"Ad1\", \"object_story_spec\": <SPEC>}}',
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
        return view('facebook.login');
    }





    private function getAccessToken($act_account)
    {
//        $cacheKey = 'com.juanpi.facebook.login.token.663043711013811';
//        $accessToken = \Cache::get($cacheKey);
//
//        if (!\Cache::has($cacheKey) || empty($accessToken)) {
//            // Token不存在就去登陆
//            redirect(route('facebook_login'));
//        }
        $result = AdAccount::with('auth')->where('ad_account_int', $act_account)->first();
        if (!empty($result) && !empty($result->auth->access_token)) {
            return $result->auth->access_token;
        }

        abort(404, '账号不存在');
    }





    private static $_faceinstance = array();

    private function getFaceBookClient()
    {
        $accessToken = $this->getAccessToken();

        if (!isset(static::$_faceinstance) || !(static::$_faceinstance instanceof \FacebookAds\Api)) {
            $appId = '663043711013811';
            $appSecret = '25faf0192fcc33270c0df783e2a1f902';

            $client = new \FacebookAds\Http\Client();
            $client->getAdapter()->setOpts(new \ArrayObject(array(
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_CAINFO => $client->getAdapter()->getCaBundlePath(),
                CURLOPT_PROXY => 'socks5h://192.168.139.171',
                CURLOPT_PROXYPORT => 1080
            )));

            $session = new \FacebookAds\Session($appId, $appSecret, $accessToken);
            $api = new \FacebookAds\Api($client, $session);
            \FacebookAds\Api::setInstance($api);
            \FacebookAds\CrashReporter::setLogger(new \FacebookAds\Logger\CurlLogger(fopen(storage_path('logs/face_curl_'.date('Y-m-d').'.log'), 'a+')));
            \FacebookAds\CrashReporter::disable();
            static::$_faceinstance = \FacebookAds\Api::instance();
        }

        return static::$_faceinstance;
    }

}
