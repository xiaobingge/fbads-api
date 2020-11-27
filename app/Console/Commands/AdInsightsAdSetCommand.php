<?php

namespace App\Console\Commands;

use App\Models\AdInsightsAdSet;
use App\Models\AdSet;

class AdInsightsAdSetCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ad-set-insights {--adset=} {--start_date=} {--end_date=} {--yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ad set insights';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $adset = $this->option('adset');
        $start_date = $this->option('start_date');
        $end_date = $this->option('end_date');
        $yesterday = $this->option('yesterday');

        $page = 1;
        do {
            if (!empty($adset)) {
                $result = AdSet::with('accountAndAuth')->where('adset_id', $adset)->paginate(20, ['*'], 'page', $page);
            } else {
                $result = AdSet::with('accountAndAuth')->where('switch_status', 0)->paginate(20, ['*'], 'page', $page);
            }
            $this->info("当前处理 {$result->currentPage()} / {$result->lastPage()}");

            $page = $result->currentPage() + 1;
            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->adset_id) && !empty($item->accountAndAuth) && !empty($item->accountAndAuth->auth) && !empty($item->accountAndAuth->auth->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $where = [
                        'date_preset' => 'yesterday',
                        'level' => 'adset',
                        'fields' => implode(',', [
                            'account_currency','account_id','account_name','action_values','actions','adset_id','adset_name','buying_type','campaign_id','campaign_name','canvas_avg_view_percent','canvas_avg_view_time','clicks','conversion_rate_ranking','conversion_values','conversions','converted_product_quantity','converted_product_value','cost_per_action_type','cost_per_conversion','cost_per_estimated_ad_recallers','cost_per_inline_link_click','cost_per_inline_post_engagement','cost_per_outbound_click','cost_per_thruplay','cost_per_unique_action_type','cost_per_unique_click','cost_per_unique_inline_link_click','cost_per_unique_outbound_click','cpc','cpm','cpp','ctr','date_start','date_stop','engagement_rate_ranking','estimated_ad_recall_rate','estimated_ad_recallers','frequency','full_view_impressions','full_view_reach','impressions','inline_link_click_ctr','inline_link_clicks','inline_post_engagement','objective','qualifying_question_qualify_answer_rate','quality_ranking','reach','social_spend','spend','unique_clicks','unique_ctr','unique_inline_link_click_ctr','unique_inline_link_clicks','unique_link_clicks_ctr'
                        ])
                    ];

                    if (!empty($start_date) && !empty($end_date)) {
                        $where['time_increment'] = 1;
                        $where['time_range'] = json_encode([
                            'since' => $start_date,
                            'until' => $end_date
                        ]);
                    } elseif (!empty($yesterday)) {
                        $where['date_preset'] = 'yesterday';
                    } else {
                        $where['date_preset'] = 'today';
                    }

                    $this->adAdSetInsights($item->adset_id, $item->accountAndAuth->auth->access_token, $where);
                }
            }

        } while ($result->hasMorePages());
    }



    protected function adAdSetInsights($adset_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$adset_id.'/insights?' . http_build_query($where),
                $access_token
            );

            /* handle the result */
            $result = $response->getDecodedBody();
            if (isset($result['data'])) {
                foreach ($result['data'] as $tmp_data) {

                    array_walk($tmp_data, function (&$val, $key) {
                        if (is_array($val)) {
                            $val = json_encode($val);
                        }
                    });

                    AdInsightsAdSet::updateOrCreate(
                        [
                            'adset_id'  => $tmp_data['adset_id'],
                            'date_start'  => $tmp_data['date_start'],
                            'date_stop'  => $tmp_data['date_stop'],
                        ],
                        $tmp_data
                    );

                }
            }
            // 下一页
            if (isset($result['paging']['next'])) {

            }


        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $this->error('AdSet_id: ' . $adset_id . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error('AdSet_id: ' . $adset_id . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }
}
