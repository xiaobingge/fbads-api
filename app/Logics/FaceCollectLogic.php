<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/1/14
 * Time: 10:03
 */
namespace App\Logics;

class FaceCollectLogic extends BaseLogic {
	private $_siteArr = [
		1 => [
			'www.onevise.com',
			'www.rinkpad.com',

		],
		2 => [
			'www.sparknion.com',
			'www.uedress.com',
			'www.zalikera.com',
			'www.lumylus.com',
			'www.sharelily.com',
			'www.jeafly.com',
			'www.ifashionfull.com',
			'www.seelily.com',
			'www.hercoco.com',
		],
		3 => [
			'www.soulmiacollection.com',
		],
		4 => [
			'shecherry.com',
		]
	];

	/**
	 * 获取待采集数据
	 * @param int $limit
	 * @param int $id
	 * @param int $siteId
	 * @return array
	 */
	public function getCollectList($limit=10, $id=0) {
		$url = 'http://open.juanpi.com/index/get_cai_collect_list?limit='.$limit;
		if($id) {
			$url .= '&id='.$id;
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

	public function insertGoodUrl($collectId, $urlArr) {
		$url = 'http://open.juanpi.com/index/add_collect_good_url';
		$postData = [
			'id' => $collectId,
			'url' => $urlArr
		];
		$return = $this->curl($url, $postData);
		if(empty($return['data'])) {
			return self::getReturnArr(1001, '添加商品链接失败', $return['data']);
		}

		$res = json_decode($return['data'], true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return self::getReturnArr(1005,'解析json数据出错', $return['data']);
		}

		return $res;
	}

	public function getGoodUrlByCollectUrl($collectUrl) {
		if(empty($collectUrl)) {
			return self::getReturnArr(1001, 'collectUrl地址非法');
		}

		$methodNum = 0;
		foreach($this->_siteArr as $num=>$siteArr) {
			foreach($siteArr as $site) {
				if(strripos($collectUrl, $site) !== false) {
					$methodNum = $num;
				}
			}
		}

		if($methodNum == 0) {
			return self::getReturnArr(1001, '暂时不支持该网站商品采集', $methodNum);
		}

		$return = $this->curl($collectUrl);
		if($return['code'] != 1000) {
			return $return;
		}


		if(empty($return['data'])) {
			return self::getReturnArr(1003, '获取页面信息错误', $return['data']);
		}

		$collectFormat = new CollectFormatLogic();
		return $collectFormat->getGoodUrlData($methodNum, $collectUrl, $return['data']);
	}



}