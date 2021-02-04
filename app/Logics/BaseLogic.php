<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/2/3
 * Time: 18:08
 */
namespace App\Logics;
class BaseLogic {
	public  function curl($url, $postData=[] ) {
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
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); //跟随重定向

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