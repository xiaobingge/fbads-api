<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);
        error_reporting(E_ALL ^ E_NOTICE);


        $this->registerFacebookSdk();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register the service providers.
     *
     * @return void
     */
    protected function registerFacebookSdk()
    {
        $this->app->singleton(\App\Utils\FacebookSdk::class, function () {
            $proxyConfig = app('config')->get('facebook-sdk.proxy');
            if ($proxyConfig['is_open'] && !empty($proxyConfig['host']) && !empty($proxyConfig['port'])) {
                $client = new \GuzzleHttp\Client([
                    'timeout' => 120,
                    'curl' => [
                        CURLOPT_PROXY => $proxyConfig['host'],
                        CURLOPT_PROXYPORT => $proxyConfig['port']
                    ],
                ]);
            } else {
                $client = new \GuzzleHttp\Client(['timeout' => 120]);
            }

            $config = app('config')->get('facebook-sdk.facebook_config');


//            static::$_clientinstance = new \App\Utils\Facebook([
//                'app_id' => '663043711013811',
//                'app_secret' => '25faf0192fcc33270c0df783e2a1f902',
//                //'app_id' => '643785509628922',
//                //'app_secret' => '88c97a5ac648dfc605302380ee8ccf0a',
//                'default_graph_version' => 'v9.0',
//                'http_client_handler' => new FGuzzle6HttpClient($client),
//                'persistent_data_handler' => new \App\Utils\FSessionDLaravelDataHandler()
//            ]);

            $config['http_client_handler'] = new \App\Utils\FGuzzle6HttpClient($client);
            $config['persistent_data_handler'] = new \App\Utils\FSessionDLaravelDataHandler();

            return new \App\Utils\FacebookSdk($config);
        });

        $this->app->alias(\App\Utils\FacebookSdk::class, 'FacebookSdk');
    }
}
