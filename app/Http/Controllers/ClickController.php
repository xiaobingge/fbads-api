<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterAuthRequest;
use App\User;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ClickController extends Controller
{
	public function index() {
		if(isset($_GET['face_url'])) {
			$url = request('face_url', '');
			$pageId = request('page_id', 0);
			$aid = request('aid', 0);
			if($url) {
				$url = base64_decode(urldecode($_GET['face_url']));
			}else {
				if($pageId && $aid == 0) {
					$url = 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=ALL&view_all_page_id='.$pageId;
				}else {
					$url = 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=All&id='.$aid.'&view_all_page_id='.$pageId.'&sort_data[direction]=desc&sort_data[mode]=relevancy_monthly_grouped';
				}
			}

			header("location:".$url);die;
		}


		if($_GET['image_url']) {
			$imageUrl = $_GET['image_url'];
			$imageUrl = stripos($imageUrl, 'https') === false ? 'https:'.$imageUrl : $imageUrl;

			$ch = curl_init($imageUrl);
			curl_setopt ($ch, CURLOPT_REFERER, 'https://www.facebook.com');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE );
			$data = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
			$httpCode = intval($info['http_code']);
			$httpSizeDownload= intval($info['size_download']);

			if($httpCode!='200' || $httpSizeDownload<1){
				return ['code'=>1000, 'msg'=>'获取成功', 'data'=>['image_info'=>'', 'width'=>0, 'height'=>0]];
			}
			$baseImageInfo = base64_encode($data);
			list($width, $height) = getimagesize($imageUrl);

			$sizeArr = getimagesize('data://image/jpeg;base64,'. $baseImageInfo);
			$width = $sizeArr[0];
			$height = $sizeArr[1];
			return ['code'=>1000, 'msg'=>'获取成功', 'data'=>['image_info'=>$baseImageInfo, 'width'=>$width, 'height'=>$height]];
		}
	}



}
