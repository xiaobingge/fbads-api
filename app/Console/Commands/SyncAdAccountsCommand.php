<?php

namespace App\Console\Commands;

use App\Models\AdAccount;

class SyncAdAccountsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:adaccounts {--account=} {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Facebook AdAccount';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $page = 1;
        $account = $this->option('account');
        $date1 = $this->option('date');

        do {
            $model = AdAccount::with('auth')->where('app_id', \FacebookSdk::getAppId())->where('type', 1);
            if (!empty($account)) {
                $model = $model->where('ad_account_int', $account);
            }

            if (!empty($date1)) {
                $model = $model->where('sync_time', '<=', $date1);
            } else {
                $model = $model->where('sync_time', '<=', date('Y-m-d', strtotime('-30 days')));
            }

            $result = $model->paginate(20, ['*'], 'page', $page);


            $page = $result->currentPage() + 1;
            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->ad_account) && !empty($item->auth) && !empty($item->auth->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $this->syncAdAccounts($item->ad_account, $item->auth->access_token, [
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'account_id',
                            'account_status',
                        ])
                    ]);
                }
            }

        } while ($result->hasMorePages());
    }


    protected function syncAdAccounts($ad_account, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$ad_account.'?' . http_build_query($where),
                $access_token
            );

            /* handle the result */
            $result = $response->getDecodedBody();
            if (!empty($result)) {
                $tmp = [
                    'name' => $result['name'],
                    'account_status' => $result['account_status'],
                    'sync_time' => date('Y-m-d')
                ];

                AdAccount::updateOrCreate(
                    [
                        'app_id' => \FacebookSdk::getAppId(),
                        'type' => 1,
                        'ad_account' => $result['id'],
                        'ad_account_int' => $result['account_id']
                    ], $tmp);
            }

        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $this->error('AdAccount: ' . $result['id'] . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error('AdAccount: ' . $result['id'] . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }

}
