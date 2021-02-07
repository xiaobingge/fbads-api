<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/1/14
 * Time: 10:03
 */
namespace App\Logics;

class FaceGoodLogic extends  BaseLogic {
	private $_siteArr = [
		500 => 'https://www.sonoup.com/',
		501 => 'https://www.rinkpad.com/'
	];

	private $_filterKeyArr = [
		'dalaline'
	];

    /**
     * @return string[]
     */
    public function getFilterKeyArr()
    {
        return $this->_filterKeyArr;
    }

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
	    $return = $this->curl($url);
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
		$return = $this->curl($url, $postData);
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

		$return = $this->curl($url);
		if($return['code'] != 1000) {
			return $return;
		}

		if(empty($return['data'])) {
			return self::getReturnArr(1003, '获取页面信息错误');
		}

		if(
			strripos($return['data'], '404 | Page Not Found') !== false
			|| strripos($return['data'], '404 Page Not Found') !== false
		){
			return self::getReturnArr(-1, '商品已经下架');
		}

		$pregArr = [
			500 => "/product:(\{.*?\}),\s*initialSlide/im",
			501 => "/goodsDetail\s*=\s*({.*?});/im",
			502 => "/const\s*product\s*=\s*(.*?);\s*const/ims",
			503 => "/same_goods_list_sale\s*=\s*(.*?}]);/ims",
			504 => "/var\s*meta\s*=\s*(.*?}});/ims",
			505 => "/var\s*google_goods_item\s*=\s*(.*?})/ims"
		];

		$site = 0;
		foreach($pregArr as $s => $preg) {
			preg_match($preg, $return['data'], $match);
			if($match[1]) {
				$site = $s;
				break;
			}
		}

		if(empty($match[1])) {
			return self::getReturnArr(1004,'匹配商品信息失败');
		}

		$goodDetailArr = json_decode($match[1], true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return self::getReturnArr(1005,'解析json数据出错');
		}

		$detailDesc = GoodFormatLogic::getGoodDetailDesc($site, $goodDetailArr, $return['data']);

		//转换数据结构
		if(in_array($site, [501,502])) {
			preg_match('/<meta\s*name="description"\s*content="(.*?)"\s*[\/]?>/', $return['data'], $match);
			if($match[1]) {
				$goodDetailArr['description'] = trim($match[1]);
			}
		}

		if($site != 500) {
			$goodFormatModel = new GoodFormatLogic();
			$goodDetailArr = $goodFormatModel->formatGoodData($site, $goodDetailArr, $return['data'], $url);
		}

		if(empty($goodDetailArr)){
			return self::getReturnArr(1006,'组合商品详细信息失败');
		}

		$goodDetailArr['detail_desc'] = str_replace($this->_filterKeyArr, '', $detailDesc);

		$priceCurrency = 'USD';
		preg_match('/"priceCurrency":\s*"(.*?)"/im', $return['data'], $match);
		if($match[1]) {
			$priceCurrency = $match[1];
		}

		$return = GoodFormatLogic::insertCollectGoodData($goodDetailArr, $site, $jpId, $collectId, $priceCurrency);
		return $return;
	}
}