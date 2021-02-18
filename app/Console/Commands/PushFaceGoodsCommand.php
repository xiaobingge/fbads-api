<?php

namespace App\Console\Commands;

use App\Models\FaceGoods;
use App\Models\FaceGoodsRs;
use App\Services\ShoplazaService;
use Illuminate\Console\Command;

class PushFaceGoodsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:goods {shop_key} {--productId=} {--ignore : Ignore number of errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push goods';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shop_key = $this->argument('shop_key');
        $productId = $this->option('productId');
        $ignore = $this->option('ignore');

        $shop_web = app(ShoplazaService::class)->getShopifyUrl($shop_key);
        if (empty($shop_web)) {
            $this->warn("没有找到编号[$shop_key]的店铺");
            return false;
        }

        $productIds = [];
        if (!empty($productId)) {
            $productIds = explode(",", $productId);
        }

        if (!empty($productIds)) {
            //首先查找是否已经上传到对应网站
            $result = FaceGoodsRs::where('type', $shop_key)->whereIn('resource_product_id', $productIds)->get(['resource_product_id'])->toArray();
            $result = array_filter(array_column($result, 'resource_product_id'));
            $productIds = array_diff($productIds, $result);
            if (empty($productIds)) {
                $this->warn("商品[$productId]的已上传到店铺[$shop_key]($shop_web)");
                return false;
            }
            $goods_cnt = FaceGoods::whereIn('product_id', $productIds)->count();
            if (empty($goods_cnt)) {
                $this->warn("商品[$productId]不存在");
                return false;
            }
        }

        $result = app(ShoplazaService::class)->createshopifygoods($shop_key, $productIds, $ignore);
        if (is_array($result)) {
            $this->info("成功处理{$result['success']}个,失败{$result['failed']}个");
        } else {
            $this->warn("出现未知错误,检查日志");
        }
    }
}
