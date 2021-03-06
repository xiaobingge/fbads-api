<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Console\Command;

class SyncAdCampaignsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:adcampaigns {--account=} {--start_date=} {--end_date=} {--yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Facebook AdCampaigns';

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
        $yesterday = $this->option('yesterday');

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
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'effective_status',
                            'status',
                            //'switch_status',
                            //'object_actions_desc',
                            //'cost_per_action_type_desc',
                            'campaign_name',
                            'campaign_id',
                            'objective',
                            'account_id',
                            'bid_strategy',
                            'daily_budget',
                            'pacing_type',
                            'budget_remaining',
                            'buying_type',
                            'lifetime_budget',
                            'promoted_object',
                            'spend_cap',
                            'topline_id',
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
                    } elseif (!empty($yesterday)) {
                        $where['date_preset'] = 'yesterday';
                    } else {
                        $where['date_preset'] = 'today';
                    }

                    $this->campaigns($item->ad_account, $item->auth->access_token, $where);
                }
            }

        } while ($result->hasMorePages());
    }


    protected function campaigns($account_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$account_id.'/campaigns?' . http_build_query($where),
                $access_token
            );

            /* handle the result */
            $result = $response->getDecodedBody();
            if (isset($result['data'])) {
                foreach ($result['data'] as $campaign) {

                    $campaign['campaign_id'] = $campaign['id'];
                    unset($campaign['id']);

                    isset($campaign['created_time']) && $campaign['created_time'] = date('Y-m-d H:i:s', strtotime($campaign['created_time']));
                    isset($campaign['end_time']) && $campaign['end_time'] = date('Y-m-d H:i:s', strtotime($campaign['end_time']));
                    isset($campaign['start_time']) && $campaign['start_time'] = date('Y-m-d H:i:s', strtotime($campaign['start_time']));
                    isset($campaign['updated_time']) && $campaign['updated_time'] = date('Y-m-d H:i:s', strtotime($campaign['updated_time']));

                    AdCampaign::updateOrCreate(
                        [
                            'campaign_id' => $campaign['campaign_id'],
                            'account_id'  => $campaign['account_id'],
                        ], $campaign);
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
