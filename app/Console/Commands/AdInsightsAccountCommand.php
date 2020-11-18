<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use Illuminate\Console\Command;

class AdInsightsAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ad-insights-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ad account insights';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        do {
            $result = AdAccount::with('auth')->where('status', 0)->paginate(20);
            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->ad_account) && !empty($item->auth) && !empty($item->auth->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $this->adAccountInsights($item->ad_account, $item->auth->access_token, [
                        'date_preset' => 'yesterday',
                        'level' => 'account',
                        'fields' => implode(',', [

                            'account_currency',
                            // 'account_id',
                            'account_name',
                            'action_values',
                            'ad_id',
                            'adset_id',
                            'campaign_id',
                            'objective',
                            'account_id',
                            'buying_type',
                            'clicks',
                            'cpc',
                            'cpm',
                            'cpp',
                            'ctr',
                            'date_start',
                            'date_stop',
                            'full_view_impressions',
                            'impressions',
                            // 'action_values',
                            'actions',
                        ])
                    ]);
                }
            }

        } while ($result->hasMorePages());
    }



    protected function adAccountInsights($account_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/insights?' . http_build_query($where),
                $access_token
            );

            /* handle the result */
            $result = $response->getDecodedBody();
            print_r($result);
            return 0;
            if (isset($result['data'])) {
                foreach ($result['data'] as $campaign) {


                }
            }
            // 下一页
            if (isset($result['paging']['next'])) {

            }


        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $this->error($account_id . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error($account_id . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }
}
