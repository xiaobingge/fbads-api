<?php

namespace App\Http\Controllers;

use App\Services\ShoplazaService;

class FaceApiController extends Controller {

	private $_shoplazaService;

	public function __construct( ShoplazaService $shoplazaService) {
		$this->_shoplazaService = $shoplazaService;
	}

	public function getShopifyCollectCount() {
		$where =  request('where');
		$siteId = request('site_id');

		if(!is_array($where) || !is_numeric($siteId)) {
			return self::getReturnArr(1001, '参数错误');
		}

		$count = $this->_shoplazaService->getShopifyCollectCount($where, $siteId);
		return self::getReturnArr(1000, '获取成功', ['count'=>$count]);
	}

	public function getShopifyCollectList() {
		$where =  request('where');
		$siteId = request('site_id');
		$limit = request('limit');
		$nextUrl = request('next_url');

		if(!is_array($where) || !is_numeric($siteId) || !is_numeric($limit)) {
			return self::getReturnArr(1001, '参数错误');
		}

		$return = $this->_shoplazaService->getShopifyCollectList($where, $siteId, $limit, $nextUrl);
		if($return === false) {
			return ['code'=>1002, 'msg'=>'数据获取失败'];
		}

		return ['code'=>1000, 'msg'=>'获取成功', 'data'=>$return];
	}


	public function getShopifyGoodCount() {
		$where =  request('where');
		$siteId = request('site_id');

		if(!is_array($where) || !is_numeric($siteId)) {
			return self::getReturnArr(1001, '参数错误');
		}

		$count = $this->_shoplazaService->getShopifyGoodCount($where, $siteId);
		return self::getReturnArr(1000, '获取成功', ['count'=>$count]);
	}

	public function getShopifyGoodList() {
		$where =  request('where');
		$siteId = request('site_id');
		$limit = request('limit');
		$nextUrl = request('next_url');

		if(!is_array($where) || !is_numeric($siteId) || !is_numeric($limit)) {
			return self::getReturnArr(1001, '参数错误');
		}

		$return = $this->_shoplazaService->getShopifyGoodList($where, $siteId, $limit, $nextUrl);
		if($return === false) {
			return ['code'=>1002, 'msg'=>'数据获取失败'];
		}

		return ['code'=>1000, 'msg'=>'获取成功', 'data'=>$return];
	}

	public static function  getReturnArr($code, $msg, $dataArr=[]) {
		return [
			'code' => $code,
			'msg' => $msg,
			'data' => $dataArr
		];
	}


}