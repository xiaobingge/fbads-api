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

		preg_match("/product:(\{.*?\}),\s*initialSlide/im", $return['data'], $match);
		if($match[1]) {
			$site = 500;
		} else {
			preg_match("/goodsDetail\s*=\s*({.*?});/im", $return['data'], $match);
			$site = 501;
		}

		$detailDesc = self::getGoodDetailDesc($site, $return['data']);

		if(empty($match[1])) {
			return self::getReturnArr(1004,'匹配商品信息失败', $return['data']);
		}
		$goodDetailArr = json_decode($match[1], true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return self::getReturnArr(1005,'解析json数据出错', $return['data']);
		}

		$priceCurrency = 'USD';
		preg_match('/"priceCurrency":\s*"(.*?)"/im', $return['data'], $match);
		if($match[1]) {
			$priceCurrency = $match[1];
		}

		//转换数据结构
		if($site == 501) {
			preg_match('/<meta\s*name="description"\s*content="(.*?)"\s*\/>/', $return['data'], $match);
			if($match[1]) {
				$goodDetailArr['description'] = trim($match[1]);
			}
			$goodDetailArr = self::formatGoodData_501($goodDetailArr);
		}

		$goodDetailArr['detail_desc'] = $detailDesc;

		$return = $this->insertCollectGoodData($goodDetailArr, $site, $jpId, $collectId, $priceCurrency);
		return $return;
	}

	public static function getGoodDetailDesc($site, $data) {
		$detailDesc = '';
		if($site == 500) {
			//preg_match('/(<div\s*style="width:\s*100%;\s*margin-bottom:\s*20px;">.*?)<\/div>\s*<input/ims', $data, $detailMatch);
			preg_match('/product_detail_description_content">(.*?)(?:(<\/div>\s*<input)|(<p>\s*<script))/ims', $data, $detailMatch);
			if($detailMatch) {
				$detailDesc = preg_replace_callback(
					'#(data-src="https://img\.staticdj\.com/\w+_{width}\.(?:jpg|gif|bmp|bnp|png|jpeg)"\s*alt=""\s*width="(\d+)").*?#',
					function ($matches) {
						return str_replace(['data-src', '{width}'], ['src',$matches[2]], $matches[1]);
					}, $detailMatch[1]);
			}

		}else {
			preg_match('/<div\s*class="accord-cont\s*description-html">(.*?)<\/div>/ims', $data, $detailMatch);
			if($detailMatch) {
				$detailDesc = $detailMatch[1];
			}
		}
		return trim($detailDesc);
	}

	public function insertCollectGoodData($goodDetailArr, $site=0, $jpId=0, $collectId='', $priceCurrency='') {
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

		$option1 = array_unique(array_column($goodDetailArr['variants'], 'option1'));
		$option2 = array_unique(array_column($goodDetailArr['variants'], 'option2'));
		$time = time().mt_rand(1,9999);
		if($option1) {
			$goodDetailArr['options'][] = [
				'id' => sprintf('%u',crc32($goodArr['spu'].implode('',$option1).$time)),
				'product_id' => $goodArr['spu'],
				'name' => 'Color',
				'position' => 1,
				'values' => $option1
			];
		}
		if($option2) {
			$goodDetailArr['options'][] = [
				'id' => sprintf('%u',crc32($goodArr['spu'].implode('',$option2).$time)),
				'product_id' => $goodArr['spu'],
				'name' => 'Size',
				'position' => 2,
				'values' => $option2
			];
		}

		$goodDetailArr['inventory_quantity'] = $totalStockNum;
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
			'tags' => $goodInfo['tags'] ?: '',
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

		$cookieArr = [
			'__cfduid=da1cd1fd658f960b024569fb103b25efc1610615791; locale=zh; ccy=HKD; _opu=op_c2098c0fdb6c754d_17704ba6618_40c1; _opud=op_f05152be6ccb4a91_17704ba6618_8f9f; _odevice=-359279312; _ga=GA1.2.14379895.1610692062; _gid=GA1.2.1197358344.1610692062; ftr_ncd=6; _scid=3efbf76d-fc3a-4291-b460-c30a0014abc8; _sctr=1|1610640000000; _fbp=fb.1.1610692069882.1434697141; _pin_unauth=dWlkPVptRXdORE5qTnprdFl6YzRZaTAwTlRGbExUZzFZall0WXpRM00yTmtNV1l6TlRVMQ; _uetsid=c56614b056fa11eba98d996484fa462c; _uetvid=c566a6d056fa11ebb8b3edd628e0e922; forterToken=b4a6a3fbf5e24262bca6319ce02ca490_1610692684704__UDF43_9ck'
		];

		shuffle($cookieArr);
		$cookie = $cookieArr[0];

		$header = array(
			'User-Agent:'.$userAgent,
			//'Cookie:'.$cookie,
			'Referer:'.$referer,
		);
		//初始化
		$curl = curl_init();

		if(strtoupper(substr(PHP_OS,0,3))==='WIN') {
			curl_setopt($curl, CURLOPT_PROXY, 'socks5h://localhost');
			curl_setopt($curl, CURLOPT_PROXYPORT,1080);
		}

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