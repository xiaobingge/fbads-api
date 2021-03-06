<?php
/**
 * Created by PhpStorm.
 * User: jp
 * Date: 2021/2/3
 * Time: 14:31
 */
namespace App\Logics;

class CollectFormatLogic extends BaseLogic {

	public function getGoodUrlData($num, ...$paramsArr){
		$method = 'getGoodURL_'.$num;
		return call_user_func_array([$this, $method], $paramsArr);
	}

	private  function getGoodURL_1($collectUrl, $htmlStr) {
		$regexArr = [
			'pageNo' => '#<a\s*rel="nofollow"\s*class="pagination-item waves-effect waves-classic" href="(?:.*?)">(?<pageNos>\d+)</a>#',
			'goodUrl' => '#<div\s*class="product\s*product4\s*product-box"(?:.*?)<a\s*href="(?<urls>.*?)"#sxm',
			'linkMuban' => $collectUrl.'?pageNo=#pageNum#'
		];

		return $this->_getUrlByRegex($collectUrl, $regexArr, $htmlStr);
	}

	private  function getGoodURL_2($collectUrl, $htmlStr) {
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
			$res = $this->curl($url);
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
			return self::getReturnArr(1006, '采集商品链接失败', $urlArr);
		}

		foreach($urlArr as &$url) {
			$url = $webHost.$url;
		}

		return self::getReturnArr(1000, '获取链接地址成功', $urlArr);
	}

	private function getGoodURL_3($collectUrl, $htmlStr) {
		$hostArr = parse_url($collectUrl);
		$pathArr = explode('.', $hostArr['path']);
		$regexArr = [
			'pageNo' => '#<li>\s*<a\s*href="/(?:.*?)html(?:.*?)"\s*>(?<pageNos>\d+)</a>#ixsm',
			'goodUrl' => '#class="index-good-picture\s*quick-view-link"\s*href="(?<urls>.*?)"#sxm',
			'linkMuban' => $hostArr['scheme'].'://'.$hostArr['host'].$pathArr[0].'-page-#pageNum#.'.$pathArr[1].'?'.$hostArr['query']
		];

		return $this->_getUrlByRegex($collectUrl, $regexArr, $htmlStr);
	}

	private function getGoodURL_4($collectUrl, $htmlStr) {
		$regexArr = [
			'pageNo' => '#<li>\s*<a.*?>(?<pageNos>\d+)</a>\s*</li>#ism',
			'goodUrl' => '#<div\s*class="">\s*<a\s*href="(?<urls>.*?)"\s*class="grid-link">\s*<span\s*class="grid-link__image\s*grid-link__image--loading\s*grid-link__image--product"#sxm',
			'linkMuban' => $collectUrl.'?page=#pageNum#'
		];

		return $this->_getUrlByRegex($collectUrl, $regexArr, $htmlStr);
	}

	private  function _getUrlByRegex($collectUrl, $regexArr, $htmlStr) {
		//匹配页数
		preg_match_all($regexArr['pageNo'], $htmlStr, $pageMatch);
		$maxPage = $pageMatch['pageNos'] ? max($pageMatch['pageNos']) : 1;
		$hostArr = parse_url($collectUrl);

		$goodUrlArr = [];
		for($i=1; $i<=$maxPage; $i++) {
			$link = str_replace('#pageNum#', $i, $regexArr['linkMuban']);
			$return = $this->curl($link);
			if($return['code'] != 1000) {
				return $return;
			}

			if(empty($return['data'])) {
				return self::getReturnArr(1004, '获取页面信息错误', $return['data']);
			}

			preg_match_all($regexArr['goodUrl'], $return['data'], $pageUrlMatch);
			if(empty($pageUrlMatch['urls'])) {
				return self::getReturnArr(1005, '没有匹配到商品链接', $return['data']);
			}

			foreach($pageUrlMatch['urls'] as $url) {
				if(stripos($url, $hostArr['host']) === false) {
					$goodUrlArr[] = $hostArr['scheme'].'://'.$hostArr['host'].$url;
				} else {
					$goodUrlArr[] = $url;
				}
			}
		}

		if(empty($goodUrlArr)) {
			return self::getReturnArr(1006, '采集商品链接失败', $goodUrlArr);
		}

		return self::getReturnArr(1000, '获取链接地址成功', $goodUrlArr);
	}


}