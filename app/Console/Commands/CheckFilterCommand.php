<?php

namespace App\Console\Commands;

use App\Services\ShoplazaService;
use Illuminate\Console\Command;

class CheckFilterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:filter {shop_type} {filter} {--productId=} {--handle=} {--title=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check filter';



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shop_type = $this->argument('shop_type');
        $filter = $this->argument('filter');
        $productId = $this->option('productId');
        $handle = $this->option('handle');
        $title = $this->option('title');

        app(ShoplazaService::class)->getCommandFixFilter($shop_type, $filter, ['productId' => $productId, 'handle' => $handle, 'title' => $title]);
    }
}
