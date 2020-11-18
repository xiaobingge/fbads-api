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

	}



}