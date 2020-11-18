<?php

namespace App\Http\Controllers;

use App\Utils\FGuzzle6HttpClient;
use App\Utils\FSessionDLaravelDataHandler;
use Carbon\Carbon;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use FacebookAds\Cursor;
use FacebookAds\Object\AbstractCrudObject;
use FacebookAds\Object\AdAccountUser;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\User;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    private static $_clientinstance;

    /**
     * @return \Facebook\Facebook
     * @throws FacebookSDKException
     */
    private function getFbClient()
    {
        if (empty(static::$_clientinstance) || !(static::$_clientinstance instanceof \Facebook\Facebook)) {
            $client = new \GuzzleHttp\Client([
                'timeout' => 120,
                'curl' => [
                    CURLOPT_PROXY => 'socks5h://192.168.139.171',
                    CURLOPT_PROXYPORT => 1080
                ],
            ]);

            $persistentDataHandler = new FSessionDLaravelDataHandler();

            static::$_clientinstance = new Facebook([
                'app_id' => '663043711013811',
                'app_secret' => '25faf0192fcc33270c0df783e2a1f902',
                //'app_id' => '643785509628922',
                //'app_secret' => '88c97a5ac648dfc605302380ee8ccf0a',
                'default_graph_version' => 'v9.0',
                'http_client_handler' => new FGuzzle6HttpClient($client),
                'persistent_data_handler' => $persistentDataHandler
            ]);
        }
        return static::$_clientinstance;
    }

    public function login()
    {
        $fb = $this->getFbClient();

        $helper = $fb->getRedirectLoginHelper();
        $cacheKey = 'com.juanpi.facebook.login.token.663043711013811';
        $accessToken = \Cache::get($cacheKey);

        if (!\Cache::has($cacheKey) || empty($accessToken)) {
            $helper = $fb->getRedirectLoginHelper();
            try {
                $accessTokenHelper = $helper->getAccessToken();
                print_r($accessTokenHelper);
                if ($accessTokenHelper instanceof AccessToken) {
                    // The OAuth 2.0 client handler helps us manage access tokens
//                    $oAuth2Client = $fb->getOAuth2Client();
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

                    \Cache::put($cacheKey, $accessToken, $accessTokenHelper->getExpiresAt());
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
        }

        if (!empty($accessToken)) {
            echo "You are logged in!";
            try {
                // Returns a `Facebook\Response` object
                $response = $fb->get('/me?fields=id,name', $accessToken);

                $user = $response->getGraphUser();

                \DB::insert("INSERT INTO `ad_auth` (`type`, `user_id`, `name`, `access_token`) VALUES (?, ?, ?, ?);", [
                    1, $user['id'], $user['name'], $accessToken
                ]);

            } catch(\Facebook\Exception\ResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
            } catch(\Facebook\Exception\SDKException $e) {
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
            $loginUrl = $helper->getLoginUrl('https://facebook.juanpi.com/facebook/login', $permissions);
            echo '<a href="' . $loginUrl . '">Log in with Facebook</a>';
        }
    }

    public function me()
    {
        $accessToken = $this->getAccessToken();
        $fb = $this->getFbClient();
        try {
            $response = $fb->get('/me?fields=id,name', $accessToken);
        } catch(\Facebook\Exception\ResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exception\SDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $user = $response->getGraphUser();

        return 'ID: ' . $user['id'] . '|' . 'Name: ' . $user['name'];
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

        $fb = $this->getFbClient();
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get(
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

    public function campaigns()
    {
        $this->getFaceBookClient();
        $account_id = 'act_1074776236311593';
        $user_id = '111581780701071';

        try {
            $cursor = (new \FacebookAds\Object\AdAccount($account_id))->getCampaigns();

            if ($cursor instanceof Cursor) {
                print_r($cursor->getLastResponse()->getContent());
            } else if ($cursor instanceof \FacebookAds\Http\Response) {
                print_r($cursor->getContent());
            } else if ($cursor instanceof \FacebookAds\Object\AbstractCrudObject) {
                print_r($cursor->exportData());
            }

            // Loop over objects
            foreach ($cursor as $campaign) {
                echo $campaign->{CampaignFields::NAME} . PHP_EOL;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    public function ads_archive()
    {
        $this->getFaceBookClient();
        $account_id = 'act_1074776236311593';

    }


    public function ads()
    {
        $this->getFaceBookClient();
        $account_id = 'act_1074776236311593';
        $user_id = '111581780701071';

        try {
            $ads = (new \FacebookAds\Object\AdAccount($account_id))->getAds([\FacebookAds\Object\Fields\AdFields::NAME,]);
            print_r($ads);
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    public function test151()
    {
        return view('facebook.login');
    }





    private function getAccessToken()
    {
        $cacheKey = 'com.juanpi.facebook.login.token.663043711013811';
        $accessToken = \Cache::get($cacheKey);

        if (!\Cache::has($cacheKey) || empty($accessToken)) {
            // Token不存在就去登陆
            redirect(route('facebook_login'));
        }
        return $accessToken;
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
