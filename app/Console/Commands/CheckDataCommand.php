<?php

namespace App\Console\Commands;

use App\Services\ShoplazaService;
use Illuminate\Console\Command;

class CheckDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:data {shop_type} {--productId=} {--handle=} {--title=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check shopify shoplaza data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shop_type = $this->argument('shop_type');
        $productId = $this->option('productId');
        $handle = $this->option('handle');
        $title = $this->option('title');

        if (empty($productId) && empty($handle) && empty($title)) {
            return;
        }
        $result = app(ShoplazaService::class)->getToolFixMsg($shop_type, ['productId' => $productId, 'handle' => $handle, 'title' => $title]);

        $err_msg = $result['msg'];
        if (is_array($err_msg)) {
            foreach ($err_msg as $msg) {
                $this->warn($msg);
            }
        } else{
            $this->warn($err_msg);
        }
    }
}
