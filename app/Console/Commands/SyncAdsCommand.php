<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdAd;
use Illuminate\Console\Command;

class SyncAdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Facebook Ads';

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
                    $this->getAds($item->ad_account, $item->auth->access_token, [
                        'effective_status' => json_encode([
                            'ACTIVE', 'PAUSED'
                        ]),
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
                    ]);
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