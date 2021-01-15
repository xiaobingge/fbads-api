<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/1/14
 * Time: 10:03
 */
namespace App\Logics;

use App\Models\FaceGoods;
use App\Models\FaceGoodsImage;
use App\Models\FaceGoodsOption;
use App\Models\FaceGoodsSku;

class FaceGoodLogic {
	private $_siteArr = [
		500 => 'https://www.sonoup.com/',
		501 => 'https://www.rinkpad.com/'
	];

	/**
	 * 获取待采集数据
	 * @param int $limit
	 * @param int $id
	 * @param int $siteId
	 * @return array
	 *
	 *
	 */
	public function getGoodList($limit=10, $id=0, $siteId=0) {
		$url = 'http://open.juanpi.com/index/get_cai_good_list?limit='.$limit;
		if($id) {
			$url .= '&id='.$id;
		}
		if($siteId) {
			$url .='&site_id='.$siteId;
		}
	    $return = $this->_curl($url);
		if($return['code'] != 1000) {
			return $return;
		}

		if(empty($return['data'])) {
			return self::getReturnArr(1001, '没有获取到数据', $return['data']);
		}

		$dataArr = json_decode($return['data'], true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return self::getReturnArr(1005,'解析json数据出错', $return['data']);
		}

		return self::getReturnArr(1000, '数据获取成功', $dataArr['data']);
	}

	public function updateGoodInfo($id, $data) {
		$url = 'http://open.juanpi.com/index/update_cai_good';
		$postData = [
			'id' => $id,
			'data' => $data
		];
		$return = $this->_curl($url, $postData);
		if(empty($return['data'])) {
			return self::getReturnArr(1001, '没有获取到数据', $return['data']);
		}

		$res = json_decode($return['data'], true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return self::getReturnArr(1005,'解析json数据出错', $return['data']);
		}

		return $res;
	}

	/**
	 * 采集商品信息入库
	 * @param $url
	 * @param $site
	 * @param $jpId
	 * @param $collectId
	 * @return array
	 *
	 */
	public function insertGoodInfo($url, $site, $jpId, $collectId) {
		if(empty($url)) {
			return self::getReturnArr(1001, 'url地址非法');
		}

		if(empty($this->_siteArr[$site])) {
			return self::getReturnArr(1002, '没法采集该网站');
		}

		$return = $this->_curl($url);
		if($return['code'] != 1000) {
			return $return;
		}

		if(empty($return['data'])) {
			return self::getReturnArr(1003, '获取页面信息错误', $return['data']);
		}

		if(in_array($site,[500])) {
			preg_match("/product:(\{.*?\}),\s*initialSlide/im", $return['data'], $match);
		} else {
			preg_match("/goodsDetail\s*=\s*({.*?});/im", $return['data'], $match);

		}
		if(empty($match[1])) {
			return self::getReturnArr(1004,'匹配商品信息失败', $return['data']);
		}
		$goodDetailArr = json_decode($match[1], true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return self::getReturnArr(1005,'解析json数据出错', $return['data']);
		}

		//转换数据结构
		if($site == 501) {
			$goodDetailArr = self::formatGoodData_501($goodDetailArr);
		}

		$return = $this->insertCollectGoodData($goodDetailArr, $site, $jpId, $collectId);
		return $return;
	}

	public function insertCollectGoodData($goodDetailArr, $site=0, $jpId=0, $collectId='') {
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

		$goodArr = self::getGoodDetailInfo($goodDetailArr, $site, $jpId, $collectId);

		return self::addGoodDetail($goodArr);
	}

	public static  function addGoodDetail($goodDetailArr) {
		$goodId = FaceGoods::query()->insertGetId($goodDetailArr['good_info']);
		if(!$goodId) {
			return self::getReturnArr(1001,'商品ID：'.$goodDetailArr['product_id'].'添加失败');
		}

		if($goodDetailArr['sku_info']) {
			$flag = FaceGoodsSku::query()->insert($goodDetailArr['sku_info']);
			if(!$flag) {
				return self::getReturnArr(1001,'商品ID：'.$goodDetailArr['product_id'].'sku添加失败');
			}
		}

		if($goodDetailArr['image_info']) {
			FaceGoodsImage::query()->insert($goodDetailArr['image_info']);
		}

		if($goodDetailArr['option_info']) {
			FaceGoodsOption::query()->insert($goodDetailArr['option_info']);
		}

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

		$goodDetailArr['inventory_quantity'] = $totalStockNum;
		return $goodDetailArr;
	}

	public static  function getGoodDetailInfo($goodInfo, $type, $jpId, $collectId) {
		$goodBaseInfo = [
			'jp_id' => $jpId,
			'title' => self::filterEmoji($goodInfo['title']),
			'cid' => $collectId,
			'type' => $type,
			'handle' => $goodInfo['handle'] ?: '',
			'tags' => $goodInfo['tags'] ?: '',
			'description' => $goodInfo['description'] ? mb_substr(self::filterEmoji($goodInfo['description']),0,200) : '',
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
				$sku = [
					'image' =>  $image,
					'barcode' =>  $sku['barcode'] ?: '',
					'compare_at_price' =>  $sku['compare_at_price'] ?: 0,
					'created_at' =>  date('Y-m-d H:i:s', strtotime($sku['created_at'])),
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
		$str = preg_replace_callback( '/./u',
			function (array $match) {
				return strlen($match[0]) >= 4 ? '' : $match[0];
			},
			$str);
		return $str;
	}

	private function _curl($url, $postData=[] ) {
		$userAgentAr = [
			//'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:48.0) Gecko/20100101 Firefox/48.0',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36',
			'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)',
		];

		shuffle($userAgentAr);
		$userAgent = $userAgentAr[0];

		$refererArr = [
			'https://www.facebook.com/ads/library/?active_status=all&ad_type=political_and_issue_ads&country=US',
			'https://www.facebook.com',
		];

		shuffle($refererArr);
		$referer = $refererArr[0];

		$header = array(
			'User-Agent:'.$userAgent,
			'Referer: '.$referer
		);

		//初始化
		$curl = curl_init();
		/*curl_setopt($curl, CURLOPT_PROXY, 'socks5h://localhost');
		curl_setopt($curl, CURLOPT_PROXYPORT,$port);
		//设置抓取的url*/
		curl_setopt($curl, CURLOPT_URL, $url);
		//设置头文件的信息作为数据流输出
		curl_setopt($curl, CURLOPT_HEADER,0);
		//设置获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		// 超时设置
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		// 设置请求头
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE );

		if($postData) {
			$postData = json_encode($postData);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($postData))
			);

			//设置post方式提交
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		}

		//执行命令
		$data = curl_exec($curl);

		// 显示错误信息
		if (curl_error($curl)) {
			curl_close($curl);
			return self::getReturnArr(1001, curl_error($curl), $data);
		}

		return self::getReturnArr(1000, '获取成功', $data);
	}

	public static function getReturnArr($code, $msg , $data=[]) {
		return [
			'code' => $code,
			'msg' => $msg,
			'data' => $data
		];
	}

}