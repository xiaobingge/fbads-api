<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdAuth;
use Illuminate\Console\Command;

class CheckAdAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:adaccounts {--user_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check adaccounts';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $page = 1;
        $userId = $this->option('user_id');

        do {
            $model = AdAuth::where('app_id', \FacebookSdk::getAppId())->where('type', 1);
            if (!empty($userId)) {
                $model = $model->where('user_id', $userId);
            }

            $result = $model->paginate(20, ['*'], 'page', $page);

            $page = $result->currentPage() + 1;
            $items = $result->items();
            if (count($items) <= 0) {
                break;
            }

            foreach ($items as $item) {
                if (!empty($item->user_id) && !empty($item->access_token)) {
                    // effective_status=%5B%22ACTIVE%22%2C%22PAUSED%22%5D&fields=name%2Cobjective
                    $this->syncAdAccounts($item->user_id, $item->access_token, [
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'account_id',
                            'account_status',
                            'timezone_name',
                            'currency',
                            'spend_cap',
                            'amount_spent',
                        ])
                    ]);
                }
            }

        } while ($result->hasMorePages());
    }


    protected function syncAdAccounts($user_id, $access_token, $where = [])
    {
        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = \FacebookSdk::get(
                '/'.$user_id.'/adaccounts?' . http_build_query($where),
                $access_token
            );
            $edge = $response->getGraphEdge();
            if (\FacebookSdk::next($edge)) {
                $this->updateOrCreateAdAccount($user_id, $edge->asArray());
                while ($edge = \FacebookSdk::next($edge)) {
                    $this->updateOrCreateAdAccount($user_id, $edge->asArray());
                }
            } else {
                $this->updateOrCreateAdAccount($user_id, $edge->asArray());
            }

        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $this->error('AdAccount: ' . $user_id . 'Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $this->error('AdAccount: ' . $user_id . 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }


    protected function updateOrCreateAdAccount($user_id, $data) {
        foreach ($data as $result) {
            $tmp = [
                'name' => $result['name'],
                'account_status' => $result['account_status'],
                'timezone_name' => $result['timezone_name'],
                'currency' => $result['currency'],
                'spend_cap' => intval($result['spend_cap']),
                'amount_spent' => intval($result['amount_spent']),
                'sync_time' => date('Y-m-d'),
                'user_id' => $user_id,
            ];

            AdAccount::updateOrCreate(
                [
                    'app_id' => \FacebookSdk::getAppId(),
                    'type' => 1,
                    'ad_account' => $result['id'],
                    'ad_account_int' => $result['account_id']
                ], $tmp);
        }
    }


}
