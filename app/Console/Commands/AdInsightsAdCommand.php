<?php

namespace App\Console\Commands;

use App\Models\AdAd;
use App\Models\AdInsightsAd;

class AdInsightsAdCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ad-insights {--ad=} {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ad insights';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ad = $this->option('ad');
        $start_date = $this->option('start_date');
        $end_date = $this->option('end_date');

        $page = 1;
        do {
            if (!empty($ad)) {
                $result = AdAd::with('accountAndAuth')->where('ad_id', $ad)->paginate(20, ['*'], 'page', $page);
            } else {
                $result = AdAd::with('accountAndAuth')->where('switch_status', 0)->paginate(20, ['*'], 'page', $page);
            }
            $page = $result->currentPage() + 1;
            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->ad_id) && !empty($item->accountAndAuth) && !empty($item->accountAndAuth->auth) && !empty($item->accountAndAuth->auth->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $where = [
                        'level' => 'ad',
                        'fields' => implode(',', [
                            'account_currency','account_id','account_name','action_values','actions','ad_id','ad_name','adset_id','adset_name','buying_type','campaign_id','campaign_name','canvas_avg_view_percent','canvas_avg_view_time','clicks','conversion_rate_ranking','conversion_values','conversions','converted_product_quantity','converted_product_value','cost_per_action_type','cost_per_conversion','cost_per_estimated_ad_recallers','cost_per_inline_link_click','cost_per_inline_post_engagement','cost_per_outbound_click','cost_per_thruplay','cost_per_unique_action_type','cost_per_unique_click','cost_per_unique_inline_link_click','cost_per_unique_outbound_click','cpc','cpm','cpp','ctr','date_start','date_stop','engagement_rate_ranking','estimated_ad_recall_rate','estimated_ad_recallers','frequency','full_view_impressions','full_view_reach','impressions','inline_link_click_ctr','inline_link_clicks','inline_post_engagement','objective','qualifying_question_qualify_answer_rate','quality_ranking','reach','social_spend','spend','unique_clicks','unique_ctr','unique_inline_link_click_ctr','unique_inline_link_clicks','unique_link_clicks_ctr'
                        ])
                    ];

                    if (!empty($start_date) && !empty($end_date)) {
                        $where['time_increment'] = 1;
                        $where['time_range'] = json_encode([
                            'since' => $start_date,
                            'until' => $end_date
                        ]);
                    } else {
                        $where['date_preset'] = 'yesterday';
                    }

                    $this->adAdInsights($item->ad_id, $item->accountAndAuth->auth->access_token, $where);
                }
            }

        } while ($result->hasMorePages());
    }



    protected function adAdInsights($ad_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$ad_id.'/insights?' . http_build_query($where),
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

                    AdInsightsAd::updateOrCreate(
                        [
                            'ad_id'  => $tmp_data['ad_id'],
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
            $this->error('Ad_id: ' . $ad_id . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error('Ad_id: ' . $ad_id . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }
}
