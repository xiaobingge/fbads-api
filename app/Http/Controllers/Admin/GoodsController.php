<?php

namespace App\Http\Controllers\Admin;

use App\Models\FaceGoods;
use App\Models\FaceGoodsImage;
use App\Models\FaceGoodsOption;
use App\Models\FaceGoodsRs;
use App\Models\FaceGoodsSku;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GoodsController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        // $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        //$sort = $request->input('sort','+id');
        $keyword = $request->input('keyword','');
        //$export = $request->input('export',0);
        //$ep = $sort == '+id' ? 'asc' :'desc';
        $model = FaceGoods::where('id','>',0);

        if(!empty($type) && !empty($keyword)){
            switch($type){
                case 1:
                    $model = $model->where(['jp_id'=>$keyword]);
                    break;
                case 2:
                    $model = $model->where(['title'=>$keyword]);
                    break;
                case 3:
                    $model = $model->where(['product_id'=>$keyword]);
                    break;
            }
        }

        $columns = ['id', 'jp_id', 'title', 'type', 'product_id', 'is_delete', 'is_sync_pic', 'created_at'];

        $result = $model->orderBy('id', 'desc')->paginate($limit, $columns);

        return success(['items' => $result->items(), 'total' => $result->total()]);
    }

    public function detail(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return error(2001, 'id 为空');
        }

        $fGoods = FaceGoods::where('id', $id)->first();
        if (empty($fGoods)) {
            return error(2002, '商品未找到');
        }

        $pushItems = [];
        $rsGoods = FaceGoodsRs::where('resource_product_id', $fGoods->product_id)->get();
        if (!empty($rsGoods)) {
            $pushItems[] = [
                'product_id' => $rsGoods->product_id,
                'shop_type' => $rsGoods->shop_type,
                'shop_index' => $rsGoods->shop_index,
                'add_time' => $rsGoods->add_time
            ];
        }

        return success(['detail' => $fGoods->toArray(), 'push_items' => $pushItems]);
    }

    public function goods_image(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return error(2001, 'id 为空');
        }

        $fGoodsImages = FaceGoodsImage::where('product_id', $id)->get();
        if ($fGoodsImages->isEmpty()) {
            return error(2002, '商品图片未找到');
        }

        $items = [];
        foreach ($fGoodsImages->items() as $val) {
            $items[] = [
                'position' => $val->position,
                'width'    => $val->width,
                'height'   => $val->height,
                'src'      => $val->src
            ];
        }

        return success(['items' => $items]);
    }

    public function goods_sku(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return error(2001, 'id 为空');
        }

        $fGoodsSkus = FaceGoodsSku::where('product_id', $id)->get();
        if ($fGoodsSkus->isEmpty()) {
            return error(2002, '商品Sku未找到');
        }

        $items = [];
        foreach ($fGoodsSkus->items() as $val) {
            $items[] = [
                'image'     => json_decode($val->image, true),
                'barcode'   => $val->barcode,
                'price'     => $val->price,
                'sku'       => $val->sku,
                'inventory_quantity' => $val->inventory_quantity,
                'title'     => $val->title,
                'inventory_policy' => $val->inventory_policy,
                'grams'     => $val->grams,
                'option1'   => $val->option1,
                'option2'   => $val->option2,
                'option3'   => $val->option3,
                'tax_code'  => $val->tax_code,
                'weight'    => $val->weight,
                'weigh_unit' => $val->weigh_unit,
            ];
        }

        return success(['items' => $items]);
    }

    public function goods_option(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return error(2001, 'id 为空');
        }

        $fGoodsOpts = FaceGoodsOption::where('product_id', $id)->get();
        if ($fGoodsOpts->isEmpty()) {
            return error(2002, '商品Opts未找到');
        }

        $items = [];
        foreach ($fGoodsOpts->items() as $val) {
            $items[] = [
                'values' => json_decode($val->values, true),
                'name'   => $val->name,
            ];
        }

        return success(['items' => $items]);
    }

    public function fail_hash()
    {
        try {
            $shoplaza_faile_hash = 'redis_goods_list_faile_shopify_hash';
            $keys = \Redis::connection('default')->hkeys($shoplaza_faile_hash);
            return success(['items' => $keys]);
        }catch (\Exception $e) {
            return error(2001, $e->getMessage());
        }
    }

    public function fail_push(Request $request)
    {
        $productId = $request->input('id');
        if (empty($productId)) {
            return error(2001, 'id 为空');
        }
        \Artisan::call("push:goods", ['shop_key' => 202, '--productId' => $productId, '--ignore' => true]);
        return success();
    }
}
