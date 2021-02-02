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

class FaceCollectLogic {
	private $_siteArr = [
		1 => ['www.onevise.com', 'www.rinkpad.com'],
		2 => ['www.sparknion.com'],
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

	public function insertGoodUrl($collectId, $urlArr) {
		$url = 'http://open.juanpi.com/index/add_collect_good_url';
		$postData = [
			'id' => $collectId,
			'url' => $urlArr
		];
		$return = $this->_curl($url, $postData);
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

		$return = $this->_curl($collectUrl);
		if($return['code'] != 1000) {
			return $return;
		}

		if(empty($return['data'])) {
			return self::getReturnArr(1003, '获取页面信息错误', $return['data']);
		}

		$method= 'getGoodURL_'.$methodNum;
		return $this->$method($collectUrl, $return['data']);
	}

	public function getGoodURL_2($collectUrl, $htmlStr) {
		//得到collect_id
		preg_match('#collection_id:\s*\'(.*?)\',#', $htmlStr, $match);
		if(empty($match)) {
			return self::getReturnArr(1001, 'collect_id获取失败');
		}
		$collectId = $match[1];
		$hostArr = parse_url($collectUrl);
		$page = 0;
		$urlArr = [];
		$limit  = 40;
		$sortBy = 'manual';
		$webHost = $hostArr['scheme'].'://'.$hostArr['host'];
		while (true) {
			$url = $webHost.'/api/collections/'.$collectId.'/products?page='.$page.'&sort_by='.$sortBy.'&limit='.$limit.'&tags=&price=';
			$res = $this->_curl($url);
			if(empty($res)) {
				break;
			}
			$dataArr = json_decode($res['data'], true);
			if(empty($dataArr['data'])) {
				break;
			}

			$urlArr = array_merge($urlArr, array_column($dataArr['data']['products'], 'url'));
			$totals = $dataArr['data']['products_count'];

			if($dataArr['data']['has_more'] != 1 || count($urlArr) >= $totals) {
				break;
			}

			$j = mt_rand(1,100);
			if($j > 50) {
				sleep(1);
			}

			$page++;
		}

		if(empty($urlArr)) {
			self::getReturnArr(1006, '采集商品链接失败', $urlArr);
		}

		foreach($urlArr as &$url) {
			$url = $webHost.$url;
		}

		return self::getReturnArr(1000, '获取链接地址成功', $urlArr);
	}

	private function getGoodURL_1($collectUrl, $htmlStr) {
		//匹配页数
		preg_match_all('#<a\s*rel="nofollow"\s*class="pagination-item waves-effect waves-classic" href="(?:.*?)">(?<pageNos>\d+)</a>#', $htmlStr, $pageMatch);
		$maxPage = $pageMatch['pageNos'] ? max($pageMatch['pageNos']) : 1;
		$hostArr = parse_url($collectUrl);
		$goodUrlArr = [];

		for($i=1; $i<=$maxPage; $i++) {
			$link = $collectUrl.'?pageNo='.$i;
			$return = $this->_curl($link);
			if($return['code'] != 1000) {
				return $return;
			}

			if(empty($return['data'])) {
				return self::getReturnArr(1004, '获取页面信息错误', $return['data']);
			}

			$regex = '#<div\s*class="product\s*product4\s*product-box"(?:.*?)<a\s*href="(?<urls>.*?)"#sxm';
			preg_match_all($regex, $return['data'], $pageUrlMatch);
			if(empty($pageUrlMatch['urls'])) {
				return self::getReturnArr(1005, '没有匹配到商品链接', $return['data']);
			}

			foreach($pageUrlMatch['urls'] as $url) {
				$goodUrlArr[] = $hostArr['scheme'].'://'.$hostArr['host'].$url;
			}
		}


		if(empty($goodUrlArr)) {
			self::getReturnArr(1006, '采集商品链接失败', $goodUrlArr);
		}

		return self::getReturnArr(1000, '获取链接地址成功', $goodUrlArr);
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