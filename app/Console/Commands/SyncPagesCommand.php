<?php

namespace App\Console\Commands;

use App\Models\AdPage;

class SyncPagesCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Facebook Pages';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $page = 1;

        do {
            $result = AdPage::where('status', 1)->paginate(20, ['*'], 'page', $page);

            $page = $result->currentPage() + 1;
            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->page_id) && !empty($item->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $this->getFacebookPage($item->page_id, $item->access_token, [
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'global_brand_page_name',
                            'link',
                            'picture',
                            'is_published'
                        ])
                    ]);
                }
            }

        } while ($result->hasMorePages());
    }


    protected function getFacebookPage($page_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$page_id.'?' . http_build_query($where),
                $access_token
            );

            /* handle the result */
            $ad_result = $response->getDecodedBody();
            if (!empty($ad_result)) {
                $ad_page = [
                    'name' => $ad_result['name'],
                    'global_brand_page_name' => $ad_result['global_brand_page_name'],
                    'link' => $ad_result['link'],
                    'is_published' => $ad_result['is_published']
                ];

                isset($ad_result['picture']) && $ad_page['picture'] = json_encode($ad_result['picture']);

                AdPage::updateOrCreate(
                    [
                        'page_id' => $ad_result['id']
                    ], $ad_page);
            }

        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $this->error($page_id . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error($page_id . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }

}
