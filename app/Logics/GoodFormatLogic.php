<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/2/3
 * Time: 14:31
 */
namespace App\Logics;

use App\Models\FaceGoods;
use App\Models\FaceGoodsImage;
use App\Models\FaceGoodsOption;
use App\Models\FaceGoodsSku;

class GoodFormatLogic  {
	public function formatGoodData($num, ...$paramsArr){
		$method = 'formatGoodData_'.$num;
		return call_user_func_array([$this, $method], $paramsArr);
	}

	public static function getGoodDetailDesc($site, $goodDetailArr, $data) {
		$detailDesc = '';
		if($site == 502) {
			$detailDesc = $goodDetailArr['description'];
		}

		if($site == 500) {
			$pregArr = [
				'#product_detail_description_content">(.*?)(?:(</div>\s*<input)|(<p>\s*<script))#ims',
				'#<label\s*class="dj_skin_text product-info__desc-tab-header"\s*for="r-1539149753700-1">\s*(?:.*?)\s*<i\s*class="sep-font\s*sep-font-angle-down-strong"></i>\s*</label>\s*<div\s*class="product-info__desc-tab-content">(.*?)(?:(<\/div>\s*<input)|(<p>\s*<script))#ims',
				'#class="product-info__desc-tab-content">(.*?)(?:(<\/div>\s*<input)|(<p>\s*<script))#ims',
			];
			foreach($pregArr as $preg) {
				preg_match($preg, $data, $detailMatch);
				if($detailMatch) {
					$detailDesc = preg_replace_callback(
						'#(data-src="https://img\.staticdj\.com/\w+_{width}\.(?:jpg|gif|bmp|bnp|png|jpeg)"\s*(?:alt="")*?\s*width="(\d+)").*?#',
						function ($matches) {
							return str_replace(['data-src', '{width}'], ['src',$matches[2]], $matches[1]);
						}, $detailMatch[1]);
					break;
				}
			}
		}

		if($site == 501) {
			preg_match('/<div\s*class="accord-cont\s*description-html">(.*?)<\/div>/ims', $data, $detailMatch);
			if($detailMatch) {
				$detailDesc = $detailMatch[1];
			}
		}

		if($site == 503) {
			preg_match('/<div\s*class="good-desc">(.*?)<\/div><div\s*class="goods-delivery">/ims', $data, $detailMatch);
			if($detailMatch) {
				$detailDesc = $detailMatch[1];
			}
		}

		if($site == 504) {
			preg_match('#<ul\s*class="tab-switch__nav">.*?</ul>#ims', $data, $detailMatch);
			if($detailMatch) {
				$detailDesc = $detailMatch[0];
			}
		}

		$preg = "/<script[\s\S]*?<\/script>/i";
		$detailDesc = preg_replace($preg,"",$detailDesc);    //第四个参数中的3表示替换3次，默认是-1，替换全部
		return trim($detailDesc);
	}

	public static  function insertCollectGoodData($goodDetailArr, $site=0, $jpId=0, $collectId='', $priceCurrency='') {
		if(empty($goodDetailArr) || !is_numeric($site)) {
			return self::getReturnArr(1001,'参数错误');
		}

		if(empty($goodDetailArr['id']) || empty($goodDetailArr['title'])) {
			return self::getReturnArr(1002,'参数错误');
		}

		$productArr = FaceGoods::query()->where([['product_id', $goodDetailArr['id']], ['type',$site]])->first();
		$productArr = $productArr ? $productArr->toArray() : [];
		if($productArr) {
			return self::getReturnArr(1000,'商品已经添加');
		}

		$goodArr = self::getGoodDetailInfo($goodDetailArr, $site, $jpId, $collectId, $priceCurrency);

		return self::addGoodDetail($goodArr);
	}

	public static  function addGoodDetail($goodDetailArr) {
		\DB::beginTransaction();
		$goodId = FaceGoods::query()->insertGetId($goodDetailArr['good_info']);
		if(!$goodId) {
			\DB::rollBack();
			return self::getReturnArr(1001,'商品ID：'.$goodDetailArr['product_id'].'添加失败');
		}

		if($goodDetailArr['sku_info']) {
			$flag = FaceGoodsSku::query()->insert($goodDetailArr['sku_info']);
			if(!$flag) {
				\DB::rollBack();
				return self::getReturnArr(1001,'商品ID：'.$goodDetailArr['product_id'].'sku添加失败');
			}
		}

		if($goodDetailArr['image_info']) {
			$flag = FaceGoodsImage::query()->insert($goodDetailArr['image_info']);
			if(!$flag) {
				\DB::rollBack();
				return self::getReturnArr(1001,'商品ID：'.$goodDetailArr['product_id'].'image添加失败');
			}
		}

		if($goodDetailArr['option_info']) {
			$flag = FaceGoodsOption::query()->insert($goodDetailArr['option_info']);
			if(!$flag) {
				\DB::rollBack();
				return self::getReturnArr(1001,'商品ID：'.$goodDetailArr['product_id'].'option添加失败');
			}
		}

		\DB::commit();

		return self::getReturnArr(1000,'商品添加成功', ['good_id' => $goodId]);
	}

	public static function formatGoodData_501($goodArr) {
		$goodDetailArr['id'] = $goodArr['spu'];
		$goodDetailArr['spu'] = $goodArr['spu'];
		$goodDetailArr['title'] = $goodArr['name'];
		$goodDetailArr['handle'] = $goodArr['handle'];
		$goodDetailArr['created_at'] = date('Y-m-d');
		$goodDetailArr['image']['src'] =  $goodArr['mainImg'];
		$goodDetailArr['meta_title'] = $goodArr['seoTitle'];
		$goodDetailArr['meta_description'] = $goodArr['seoDescription'];
		$goodDetailArr['description'] = $goodArr['description'];
		$goodDetailArr['meta_keyword'] = $goodArr['seoKeywords'];
		$goodDetailArr['updated_at'] = date('Y-m-d');
		$goodDetailArr['published_at'] = date('Y-m-d');

		foreach($goodArr['images'] as $image) {
			$goodDetailArr['images'][] = [
				'src' => $image['url'],
				'id' => $image['md5'],
			];
		}

		$totalStockNum = 0;
		foreach($goodArr['skus'] as $key=>$sku) {
			$totalStockNum += $sku['stock'];
			$imgArr = [
				'src' => $sku['mainImg']
			];
			$skuArr['image'] = $imgArr;
			$skuArr['barcode'] = $sku['pmsSku'];
			$skuArr['compare_at_price'] = $sku['ccySalePrice'];
			$skuArr['price'] =  $sku['ccySalePrice'];
			$skuArr['product_id'] = $goodArr['spu'];
			$skuArr['sku'] = $sku['goodsId'];
			$skuArr['id'] = $sku['sku'];
			$skuArr['created_at'] = date('Y-m-d');
			$skuArr['inventory_quantity'] = $sku['stock'];
			$skuArr['option1'] = $sku['attrs']['color'];
			$skuArr['option2'] =  $sku['attrs']['size'];
			$skuArr['position'] =  $key+1;

			$goodDetailArr['variants'][] = $skuArr;
		}

		$goodDetailArr = self::getGoodOptions($goodDetailArr);
		$goodDetailArr['inventory_quantity'] = $totalStockNum;
		return $goodDetailArr;
	}


	public static function formatGoodData_502($goodArr) {
		$goodDetailArr['id'] = $goodArr['id'];
		$goodDetailArr['title'] = $goodArr['title'];
		$goodDetailArr['handle'] = $goodArr['handle'];
		$goodDetailArr['created_at'] = $goodArr['created_at'];
		$goodDetailArr['image']['src'] =  $goodArr['featured_image'];
		$goodDetailArr['description'] = $goodArr['description'];
		$goodDetailArr['updated_at'] = date('Y-m-d');
		$goodDetailArr['published_at'] = $goodArr['published_at'];
		$goodDetailArr['vendor'] = $goodArr['vendor'];
		$goodDetailArr['tags'] = $goodArr['tags'] ? implode(',', $goodArr['tags']) : '';

		foreach($goodArr['images'] as $key=>$image) {
			$goodDetailArr['images'][] = [
				'src' => $image,
				'id' => sprintf('%u',crc32($goodArr['id'].md5($image).time().$key))
			];
		}

		$totalStockNum = 0;
		foreach($goodArr['variants'] as $key=>$sku) {
			$stock = $sku['stock'] ? $sku['stock'] : mt_rand(100,2000);
			$totalStockNum += $stock;
			$imgArr = [
				'src' => $sku['featured_image']['src'],
				'width' => $sku['featured_image']['width'],
				'height' => $sku['featured_image']['height'],
			];
			$skuArr['image'] = $imgArr;
			$skuArr['barcode'] = $sku['barcode'];
			$skuArr['compare_at_price'] = $sku['compare_at_price'];
			$skuArr['price'] =  $sku['price']/100;
			$skuArr['product_id'] = $sku['featured_image']['product_id'];
			$skuArr['sku'] = $sku['sku'];
			$skuArr['id'] = $sku['id'];
			$skuArr['created_at'] = $sku['featured_image']['created_at'];
			$skuArr['inventory_quantity'] = $stock;
			$skuArr['option1'] = $sku['option2'];
			$skuArr['option2'] =  $sku['option1'];
			$skuArr['position'] =  $key+1;

			$goodDetailArr['variants'][] = $skuArr;
		}

		$goodDetailArr = self::getGoodOptions($goodDetailArr);
		$goodDetailArr['inventory_quantity'] = $totalStockNum;
		return $goodDetailArr;
	}


	public static function formatGoodData_503($goodArr, $htmlStr, $url) {
		//匹配得到主图
		preg_match_all('#<a\s*href="javascript:void\(0\)\s*"\s*title="(?<colors>.*?)"\s*data-url="(?:.*?)"\s*data-main-img="(?<main_img>.*?)">#ims', $htmlStr, $mainImgMatch);
		if(empty($mainImgMatch['colors']) || empty($mainImgMatch['main_img'])) {
			return [];
		}

		$skuImgArr = [];
		foreach($mainImgMatch['colors'] as $k=>$v) {
			$v  =  strtolower(preg_replace('/\s*/', '', $v));
			$skuImgArr[$v] = $mainImgMatch['main_img'][$k];
		}

		//通过链接地址获取商品handle
		$hostArr = parse_url($url);
		preg_match('#/(.*?)-product(\d+)#', $hostArr['path'], $goodHandleMatch);
		if(empty($goodHandleMatch[1])) {
			return [];
		}

		$skuInfo = $goodArr[0];
		$color =  strtolower(preg_replace('/\s*/', '', $skuInfo['Color']));


		$goodDetailArr['id'] = $goodHandleMatch[1];
		$goodDetailArr['title'] = $skuInfo['goods_name'];
		$goodDetailArr['handle'] = $goodHandleMatch[1];
		$goodDetailArr['created_at'] =  date('Y-m-d H:i:s');
		$goodDetailArr['image']['src'] =  $skuImgArr[$color];
		$goodDetailArr['description'] = '';
		$goodDetailArr['updated_at'] = date('Y-m-d H:i:s');
		$goodDetailArr['published_at'] = date('Y-m-d H:i:s');
		$goodDetailArr['vendor'] = '';
		$goodDetailArr['tags'] = '';

		$totalStockNum = 0;
		$goodImageArr = [];
		foreach($goodArr as $key=>$sku) {
			$color = strtolower(preg_replace('/\s*/', '', $sku['Color']));
			$skuImage = $skuImgArr[$color];

			if(!in_array($skuImage, $goodImageArr)) {
				$goodDetailArr['images'][] = [
					'src' => $skuImage,
					'id' => sprintf('%u',crc32($goodArr['id'].md5($skuImage).time().$key))
				];

				$goodImageArr[] = $skuImage;
			}

			//sku信息
			$stock = $sku['stock'] ? $sku['stock'] : mt_rand(100,2000);
			$totalStockNum += $stock;
			$imgArr = [
				'src' => $skuImage,
				'width' => 0,
				'height' => 0,
			];
			$skuArr['image'] = $imgArr;
			$skuArr['barcode'] = $sku['goods_sn'];
			$skuArr['compare_at_price'] = $sku['market_price'];
			$skuArr['price'] =  $sku['shop_price'];
			$skuArr['product_id'] = $goodDetailArr['id'];
			$skuArr['sku'] = $sku['goods_sn'];
			$skuArr['id'] = $sku['goods_id'];
			$skuArr['created_at'] = date('Y-m-d H:i:s');
			$skuArr['inventory_quantity'] = $stock;
			$skuArr['option1'] = $sku['Color'];
			$skuArr['option2'] =  $sku['Size'];
			$skuArr['position'] =  $key+1;

			$goodDetailArr['variants'][] = $skuArr;
		}

		unset($goodImageArr);

		$goodDetailArr = self::getGoodOptions($goodDetailArr);
		$goodDetailArr['inventory_quantity'] = $totalStockNum;
		return $goodDetailArr;
	}

	public static function formatGoodData_504($goodArr, $htmlStr) {
		preg_match('#<meta\s*property="og:title"\s*content="(.*?)">#ism', $htmlStr, $titleMatch);
		if(empty($titleMatch[1])) {
			return [];
		}

		$title = $titleMatch[1];
		$isHasColor = 1;
		//得到sku图片信息
		preg_match_all('/#callBackVariant\s*\.bg_color_(?<colors>.*?)\s*\{\s*background-image:\s*url\((?<imageUrls>.*?)\);\s*\}/ism', $htmlStr, $skuImageMatch);
		if(empty($skuImageMatch['colors']) || empty($skuImageMatch['imageUrls'])) {
			 //匹配一个图片
			preg_match_all('/<div\s*class="product-single__thumbnail-image"\s*style="padding-top:(?:.*?)%;background-image:url\((?<imageUrls>.*?)\);/', $htmlStr, $skuImageMatch);
			if(empty($skuImageMatch['imageUrls'])) {
				return [];
			}
			$isHasColor = 0;
		}

		//如果没有颜色属性，就定义通用颜色common color
		$skuImgArr = [];
		if($isHasColor) {
			foreach($skuImageMatch['colors'] as $j=>$color) {
				$color = strtolower(str_replace(['-'], '', $color));
				$skuImgArr[$color] = 'https:'.str_replace('118x', '360x', $skuImageMatch['imageUrls'][$j]);
			}
		}else {
			$skuImgArr['commoncolor'] = 'https:'.$skuImageMatch['imageUrls'][0];
		}

		$goodDetailArr['id'] = $goodArr['product']['id'];
		$goodDetailArr['title'] = $title;
		$goodDetailArr['handle'] = $goodArr['product']['id'];
		$goodDetailArr['created_at'] =  date('Y-m-d H:i:s');
		$goodDetailArr['image']['src'] =  'https:'.str_replace('118x', '360x', $skuImageMatch['imageUrls'][0]);
		$goodDetailArr['description'] = '';
		$goodDetailArr['updated_at'] = date('Y-m-d H:i:s');
		$goodDetailArr['published_at'] = date('Y-m-d H:i:s');
		$goodDetailArr['vendor'] = '';
		$goodDetailArr['tags'] = '';

		$totalStockNum = 0;
		$goodImageArr = [];
		foreach($goodArr['product']['variants'] as $key=>$sku) {
			if($isHasColor) {
				$colorArr = array_filter(explode('/', $sku['public_title']));
			}else {
				$colorArr = [
					'common color',
					$sku['public_title']
				];
			}

			$color = strtolower(preg_replace('/\s*/', '', $colorArr[0]));
			$skuImage = $skuImgArr[$color];

			if(!in_array($skuImage, $goodImageArr)) {
				$goodDetailArr['images'][] = [
					'src' => $skuImage,
					'id' => sprintf('%u',crc32($goodDetailArr['id'].md5($skuImage).time().$key))
				];

				$goodImageArr[] = $skuImage;
			}

			//sku信息
			$stock = $sku['stock'] ? $sku['stock'] : mt_rand(100,2000);
			$totalStockNum += $stock;
			$imgArr = [
				'src' => $skuImage,
				'width' => 0,
				'height' => 0,
			];
			$skuArr['image'] = $imgArr;
			$skuArr['barcode'] = '';
			$skuArr['compare_at_price'] = $sku['price']/100;
			$skuArr['price'] =  $sku['price']/100;
			$skuArr['product_id'] = $goodDetailArr['id'];
			$skuArr['sku'] = $sku['sku'];
			$skuArr['id'] = $sku['id'];
			$skuArr['created_at'] = date('Y-m-d H:i:s');
			$skuArr['inventory_quantity'] = $stock;
			$skuArr['option1'] = trim($colorArr[0]);
			$skuArr['option2'] =  trim($colorArr[1]);
			$skuArr['position'] =  $key+1;

			$goodDetailArr['variants'][] = $skuArr;
		}

		unset($goodImageArr);

		$goodDetailArr = self::getGoodOptions($goodDetailArr);
		$goodDetailArr['inventory_quantity'] = $totalStockNum;
		return $goodDetailArr;
	}

	public static function getGoodOptions($goodDetailArr) {
		if(empty($goodDetailArr['variants'])) {
			return $goodDetailArr;
		}
		$option1 = array_unique(array_column($goodDetailArr['variants'], 'option1'));
		$option2 = array_unique(array_column($goodDetailArr['variants'], 'option2'));
		$time = time().mt_rand(1,9999);
		if($option1) {
			$goodDetailArr['options'][] = [
				'id' => sprintf('%u',crc32($goodDetailArr['id'].implode('',$option1).$time)),
				'product_id' => $goodDetailArr['id'],
				'name' => 'Color',
				'position' => 1,
				'values' => $option1
			];
		}
		if($option2) {
			$goodDetailArr['options'][] = [
				'id' => sprintf('%u',crc32($goodDetailArr['id'].implode('',$option2).$time)),
				'product_id' => $goodDetailArr['id'],
				'name' => 'Size',
				'position' => 2,
				'values' => $option2
			];
		}

		return $goodDetailArr;
	}

	public static  function getGoodDetailInfo($goodInfo, $type, $jpId, $collectId, $priceCurrency) {
		$goodBaseInfo = [
			'jp_id' => $jpId,
			'title' => self::filterEmoji($goodInfo['title']),
			'content' => self::filterEmoji($goodInfo['detail_desc']),
			'cid' => $collectId,
			'type' => $type,
			'handle' => $goodInfo['handle'] ?: '',
			'tags' => $goodInfo['tags'] ? self::filterEmoji($goodInfo['tags']): '',
			'description' => $goodInfo['description'] ?  self::filterEmoji($goodInfo['description'])  : '',
			'vendor' => $goodInfo['vendor'] ?: '',
			'vendor_url' => $goodInfo['vendor_url'] ?: '',
			'has_only_default_variant' => $goodInfo['has_only_default_variant'] ? 1 : 0,
			'requires_shipping' => $goodInfo['requires_shipping'] ? 1 : 0,
			'taxable' => $goodInfo['taxable'] ? 1 : 0,
			'inventory_tracking' => $goodInfo['inventory_tracking'] ? 1 : 0,
			'inventory_policy' => $goodInfo['inventory_policy'] ?: '',
			'inventory_quantity' => intval($goodInfo['inventory_quantity']),
			'published' => $goodInfo['published'] ? 1 : 0,
			'created_at' =>  date('Y-m-d H:i:s', strtotime($goodInfo['created_at'])),
			'product_id' => $goodInfo['id'],
			'note' => $goodInfo['note'] ?: '',
			'meta_title' => $goodInfo['meta_title'] ? self::filterEmoji($goodInfo['meta_title']) : '',
			'meta_description' => $goodInfo['meta_description'] ? self::filterEmoji($goodInfo['meta_description']) : '',
			'meta_keyword' => $goodInfo['meta_keyword'] ?: '',
			'need_variant_image' => $goodInfo['need_variant_image'] ?: '',
			'spu' => $goodInfo['spu'] ?: '',
			'image' => $goodInfo['image'] ?  $goodInfo['image']['src'] : '',
			'updated_at' => $goodInfo['updated_at'] ?  date('Y-m-d H:i:s', strtotime($goodInfo['updated_at'])) : null,
			'published_at' => $goodInfo['published_at'] ?  date('Y-m-d H:i:s', strtotime($goodInfo['published_at'])) : null,
		];

		$imagesInfo = [];
		if($goodInfo['images']) {
			foreach($goodInfo['images'] as $img) {
				$imgInfo = [
					'created_at' => date('Y-m-d H:i:s', strtotime($goodInfo['created_at'])),
					'image_id' => $img['id'] ?: '',
					'product_id' => $goodInfo['id'],
					'position' => 0,
					'width' => intval($img['width']),
					'height' => intval($img['height']),
					'src' => $img['src'],
					'updated_at' =>  date('Y-m-d H:i:s', strtotime($goodInfo['created_at']))
				];

				$imagesInfo[] = $imgInfo;
			}
		}

		$optionInfo = [];
		if($goodInfo['options']) {
			foreach($goodInfo['options'] as $option) {
				$optionInfo[] = [
					'option_id' =>  $option['id'],
					'product_id' => $goodInfo['id'],
					'name' => $option['name'] ?: '',
					'position' => intval($option['position']),
					'values' => $option['values'] ? json_encode($option['values']): ''
				];
			}
		}

		$skuInfo = [];
		if($goodInfo['variants']) {
			foreach($goodInfo['variants'] as $sku) {
				if($sku['images']) {
					$image = json_encode($sku['images']);
				} else {
					$image = $sku['image'] ? json_encode($sku['image']): '';
				}
				if(strtolower($priceCurrency) == 'hkd') {
					$sku['compare_at_price'] = $sku['compare_at_price']  ? bcmul($sku['compare_at_price'], 0.129, 2) : 0;
					$sku['price'] = $sku['price']  ? bcmul($sku['price'], 0.129, 2) : 0;
				}

				$sku = [
					'image' =>  $image,
					'barcode' =>  $sku['barcode'] ?: '',
					'compare_at_price' =>  $sku['compare_at_price'] ?: 0,
					'created_at' =>  $sku['created_at'] ? date('Y-m-d H:i:s', strtotime($sku['created_at'])) : date('Y-m-d H:i:s'),
					'fulfillment_service' =>  $sku['fulfillment_service'] ?: '',
					'grams' =>  $sku['grams'] ?: 0,
					'weight' =>  $sku['weight'] ?: 0,
					'weight_unit' =>  $sku['weight_unit'] ?: '',
					'sku_id' =>  $sku['id'] ?: '',
					'inventory_item_id' =>  $sku['inventory_item_id'] ?: 0,
					'inventory_management' =>  $sku['inventory_management'] ?: '',
					'inventory_policy' =>  $sku['inventory_policy'] ?: '',
					'inventory_quantity' =>  $sku['inventory_quantity'] ?: 0,
					'option1' =>  $sku['option1'] ?: '',
					'option2' =>  $sku['option2'] ?: '',
					'option3' =>  $sku['option3'] ?: '',
					'position' =>  $sku['position'] ?: '',
					'price' =>  $sku['price'] ?: 0,
					'presentment_prices' =>  $sku['presentment_prices'] ?: '',
					'product_id' =>  $goodBaseInfo['product_id'] ?: '',
					'requires_shipping' =>  $sku['requires_shipping'] ?: '',
					'sku' =>  $sku['sku'] ?: '',
					'taxable' =>  $sku['taxable'] ?: '',
					'title' =>  $sku['title'] ?: '',
					'updated_at' =>  $sku['updated_at'] ? date('Y-m-d H:i:s', strtotime($sku['updated_at'])) : null,
				];

				$skuInfo[] = $sku;
			}
		}

		return [
			'good_info' => $goodBaseInfo,
			'image_info' => $imagesInfo,
			'option_info' => $optionInfo,
			'sku_info' => $skuInfo
		];
	}

	public static function filterEmoji($str) {
		if(empty($str)){
			return $str;
		}
		$str = preg_replace_callback( '/./u',
			function (array $match) {
				return strlen($match[0]) >= 4 ? '' : $match[0];
			},
			$str);
		return $str;
	}


	public static function getReturnArr($code, $msg , $data=[]) {
		return [
			'code' => $code,
			'msg' => $msg,
			'data' => $data
		];
	}

}