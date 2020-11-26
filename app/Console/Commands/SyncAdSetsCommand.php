<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdSet;

class SyncAdSetsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:adsets {--account=} {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Facebook AdSets';

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

        $page = 1;
        do {
            if (!empty($account)) {
                $result = AdAccount::with('auth')->where('app_id', $this->appId)->where('ad_account_int', $account)->paginate(20, ['*'], 'page', $page);
            } else {
                $result = AdAccount::with('auth')->where('app_id', $this->appId)->where('status', 0)->paginate(20, ['*'], 'page', $page);
            }
            $page = $result->currentPage() + 1;
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
                            'lifetime_budget',
                            'billing_event',
                            'budget_type',
                            'bid_amount',
                            'daily_budget',
                            'campaign_id',
                            'account_id',
                            'optimization_goal',
                            'targeting',
                            'promoted_object',
                            'attribution_spec',
                            'destination_type',
                            'created_time',
                            'end_time',
                            'start_time',
                            'updated_time',
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

                    $this->getAdSets($item->ad_account, $item->auth->access_token, $where);
                }
            }

        } while ($result->hasMorePages());
    }

    protected function getAdSets($account_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/adsets' . (empty($where) ? '' : '?' . http_build_query($where)),
                $access_token
            );

            /* handle the result */
            $result = $response->getDecodedBody();
            if (isset($result['data'])) {
                foreach ($result['data'] as $adset) {

                    $tmpData = [
                        'effective_status' => $adset['effective_status'],
                        'status' => $adset['status'],
                        'lifetime_budget' => $adset['lifetime_budget'],
                        'daily_budget' => $adset['daily_budget'] ?: 0,
                        'bid_amount' => $adset['bid_amount'],
                        'billing_event' => $adset['billing_event'],
                        'name' => $adset['name'],
                        'campaign_id' => $adset['campaign_id'],
                        'optimization_goal' => $adset['optimization_goal'],
                        'targeting' => isset($adset['targeting']) ? json_encode($adset['targeting']) : '',
                        'promoted_object' => isset($adset['promoted_object']) ? json_encode($adset['promoted_object']) : '',
                        'attribution_spec' => isset($adset['attribution_spec']) ? json_encode($adset['attribution_spec']) : '',
                        'destination_type' => isset($adset['destination_type']) ? $adset['destination_type'] : '',
                    ];

                    isset($adset['created_time']) && $tmpData['created_time'] = date('Y-m-d H:i:s', strtotime($adset['created_time']));
                    isset($adset['end_time']) && $tmpData['end_time'] = date('Y-m-d H:i:s', strtotime($adset['end_time']));
                    isset($adset['start_time']) && $tmpData['start_time'] = date('Y-m-d H:i:s', strtotime($adset['start_time']));
                    isset($adset['updated_time']) && $tmpData['updated_time'] = date('Y-m-d H:i:s', strtotime($adset['updated_time']));


                    AdSet::updateOrCreate(
                        [
                            'adset_id' => $adset['id'],
                            'account_id'  => $adset['account_id'],
                        ],
                        $tmpData
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
