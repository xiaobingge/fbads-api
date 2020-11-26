<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdInsightsAccount;
use App\Models\AdOverview;

class AdInsightsAccountCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ad-insights-account {--account=} {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ad account insights';

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
                        'level' => 'account',
                        'fields' => implode(',', [
                            'account_currency','account_id','account_name','action_values','actions','buying_type',
                            'canvas_avg_view_percent','canvas_avg_view_time','clicks','conversion_rate_ranking','conversion_values','conversions','converted_product_quantity','converted_product_value','cost_per_action_type','cost_per_conversion','cost_per_estimated_ad_recallers','cost_per_inline_link_click','cost_per_inline_post_engagement','cost_per_outbound_click','cost_per_thruplay','cost_per_unique_action_type','cost_per_unique_click','cost_per_unique_inline_link_click','cost_per_unique_outbound_click','cpc','cpm','cpp','ctr','date_start','date_stop','engagement_rate_ranking','estimated_ad_recall_rate','estimated_ad_recallers','frequency','full_view_impressions','full_view_reach','impressions','inline_link_click_ctr','inline_link_clicks','inline_post_engagement','objective','qualifying_question_qualify_answer_rate','quality_ranking','reach','social_spend','spend','unique_clicks','unique_ctr','unique_inline_link_click_ctr','unique_inline_link_clicks','unique_link_clicks_ctr'
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

                    $this->adAccountInsights($item->ad_account, $item->auth->access_token, $where);
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
            \Log::info(print_r($result, 1));
            if (isset($result['data'])) {
                foreach ($result['data'] as $tmp_data) {

                    $overview = [
                        'spend' => $tmp_data['spend'] ?: 0,
                        'impression' => $tmp_data['impressions'] ?: 0,
                        'click' => $tmp_data['clicks'] ?: 0,
                        'ctr' => $tmp_data['inline_link_click_ctr'] ?: 0,
                        'cpm' => $tmp_data['cpm'] ?: 0,
                        'cpc' => $tmp_data['cost_per_inline_link_click'] ?: 0,
                        'cpc_all' => $tmp_data['cpc'] ?: 0,
                        'frequency'=>$tmp_data['frequency'] ?: 0,
                        'reach'=>$tmp_data['reach'] ?: 0,
                        'roas' => 0,
                    ];

                    if (isset($tmp_data['actions'])) {
                        foreach ($tmp_data['actions'] as $val_action) {
                            // install
                            if ('mobile_app_install' == $val_action['action_type']) {
                                $overview['install'] = $val_action['value'];
                            }

                            // add_cart
                            if ('add_to_cart' == $val_action['action_type']) {
                                $overview['add_cart'] = $val_action['value'];
                            }

                            // landing_page_view
                            if ('landing_page_view' == $val_action['action_type']) {
                                $overview['landing_page_view'] = $val_action['value'];
                            }

                            // purchase
                            if ('purchase' == $val_action['action_type']) {
                                $overview['purchase'] = $val_action['value'];
                            }

                            // cpa
                            if ('purchase' == $val_action['action_type']) {
                                $overview['cpa'] = $val_action['value'];
                            }
                        }
                    }
                    if (isset($tmp_data['action_values'])) {
                        foreach ($tmp_data['action_values'] as $val_action) {
                            // purchase_value
                            if ('purchase' == $val_action['action_type']
                                || 'omni_purchase' == $val_action['action_type']
                                || 'offsite_conversion.fb_pixel_purchase' == $val_action['action_type']) {
                                $overview['purchase_value'] = $val_action['value'];
                            }
                        }
                    }

                    // 广告花费回报 (ROAS) https://www.facebook.com/business/help/1283504535023899
                    if ($overview['purchase_value'] > 0 && $overview['spend'] > 0) {
                        $overview['roas'] = round($overview['purchase_value'] / $overview['spend'], 4);
                    }

                    AdOverview::updateOrCreate(
                        [
                            'date' => $tmp_data['date_start'],
                            'account_id'  => $tmp_data['account_id'],
                        ],
                        $overview
                    );


                    array_walk($tmp_data, function (&$val, $key) {
                        if (is_array($val)) {
                            $val = json_encode($val);
                        }
                    });

                    AdInsightsAccount::updateOrCreate(
                        [
                            'account_id'  => $tmp_data['account_id'],
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
            $this->error($account_id . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error($account_id . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }
}
