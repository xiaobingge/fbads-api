<?php

namespace App\Console\Commands;

use App\Logics\FaceGoodLogic;
use Illuminate\Support\Facades\Redis;


class FaceGoodCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faceGood:cai {--id=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集商品信息 ';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
		$id = $this->option('id');
		$faceGoodLogic = new FaceGoodLogic();
		$return  = $faceGoodLogic->getGoodList(2, $id);
		if(empty($return['data'])) {
			echo '没有要采集的数据'.PHP_EOL;
			return false;
		}

		foreach($return['data'] as $good) {
			$urlMd5 = md5($good['fc_url']);
			if(Redis::exists($urlMd5)) {
				continue;
			}

			Redis::set($urlMd5, 1);
			Redis::expire($urlMd5, 300);

			$isSuccess = 2;
			$return = $faceGoodLogic->insertGoodInfo($good['fc_url'], $good['fc_site'], $good['fc_id'], 0);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;

			if($return['code'] == 1000) {
				$isSuccess = 1;
			}


			//同步更新采集状态
			$return = $faceGoodLogic->updateGoodInfo($good['fc_id'], ['fc_status'=>$isSuccess,'fc_modify'=>date('Y-m-d H:i:s')]);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;

			Redis::del($urlMd5);
		}

		echo '脚本执行完成'.PHP_EOL;

    }

    public function test() {
    	$url = 'https://www.soulmiacollection.com/car-graphic-print-round-neck-product25853.html';
    	//$url = 'https://www.ageluville.com/collections/hoodies-sweatshirts/products/fashion-casual-happy-smiley-face-print-hoodie';
		$faceGoodLogic = new FaceGoodLogic();
		$return  = $faceGoodLogic->insertGoodInfo($url, 500, 0, 0);
		dump($return);
		die;
	}

}
