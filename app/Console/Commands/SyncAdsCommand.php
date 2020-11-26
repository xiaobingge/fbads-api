<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdAd;

class SyncAdsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ads {--account=} {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Facebook Ads';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $account = $this->option('account');
        $start_date = $this->option('start_date');
        $end_date = $this->option('end_date');

        do {
            if (!empty($account)) {
                $result = AdAccount::with('auth')->where('app_id', $this->appId)->where('ad_account_int', $account)->paginate(20);
            } else {
                $result = AdAccount::with('auth')->where('app_id', $this->appId)->where('status', 0)->paginate(20);
            }

            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->ad_account) && !empty($item->auth) && !empty($item->auth->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $where = [
                        'is_completed' => true,
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'effective_status',
                            'status',
                            'campaign_id',
                            'account_id',
                            'adset_id',
                            'creative',
                            'tracking_specs'
                        ])
                    ];

                    if (!empty($start_date) && !empty($end_date)) {
                        $where['time_range'] = json_encode([
                            'since' => $start_date,
                            'until' => $end_date
                        ]);
                    } else {
                        $where['date_preset'] = 'yesterday';
                    }

                    $this->getAds($item->ad_account, $item->auth->access_token, $where);
                }
            }

        } while ($result->hasMorePages());
    }


    protected function getAds($account_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/ads' . (empty($where) ? '' : '?' . http_build_query($where)),
                $access_token
            );

            /* handle the result */
            $result = $response->getDecodedBody();
            if (isset($result['data'])) {
                foreach ($result['data'] as $ad) {

                    $tmp_data = [
                        'effective_status' => $ad['effective_status'],
                        'status' => $ad['status'],
                        'name' => $ad['name'],
                        'adset_id' => $ad['adset_id'],
                        'campaign_id' => $ad['campaign_id'],
                        'tracking_specs' => $ad['tracking_specs'] ? json_encode($ad['tracking_specs']) : '',
                    ];

                    if (isset($ad['creative'])) {
                        if (isset($ad['creative']['creative_id'])) {
                            $tmp_data['creative_id'] = $ad['creative']['creative_id'];
                        } else {
                            $tmp_data['creative'] = json_encode($ad['creative']);
                        }
                    }


                    AdAd::updateOrCreate(
                        [
                            'ad_id' => $ad['id'],
                            'account_id'  => $ad['account_id'],
                        ],
                        $tmp_data
                    );
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
