<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/1/14
 * Time: 10:03
 */
namespace App\Logics;

class FaceGoodLogic {
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
			return self::getReturnArr(1003, '获取页面信息错误');
		}

		if(strripos($return['data'], '404 | Page Not Found') !== false){
			return self::getReturnArr(-1, '商品已经下架');
		}

		$pregArr = [
			500 => "/product:(\{.*?\}),\s*initialSlide/im",
			501 => "/goodsDetail\s*=\s*({.*?});/im",
			502 => "/const\s*product\s*=\s*(.*?);\s*const/ims",
			503 => "/same_goods_list_sale\s*=\s*(.*?}]);/ims",
			504 => "/var\s*meta\s*=\s*(.*?}});/ims",
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

		$goodDetailArr['detail_desc'] = str_replace($this->_filterKeyArr, '', $detailDesc);

		if(empty($goodDetailArr)) {
			return self::getReturnArr(1006,'组合商品详细信息失败');
		}

		$priceCurrency = 'USD';
		preg_match('/"priceCurrency":\s*"(.*?)"/im', $return['data'], $match);
		if($match[1]) {
			$priceCurrency = $match[1];
		}

		$return = GoodFormatLogic::insertCollectGoodData($goodDetailArr, $site, $jpId, $collectId, $priceCurrency);
		return $return;
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
		curl_setopt($curl, CURLOPT_MAXREDIRS,20);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

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