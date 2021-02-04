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
    protected $signature = 'faceGood:cai {--id=} {--debug=}';

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
		$debug = $this->option('debug');
		if($debug) {
			$this->test();
		}

		$faceGoodLogic = new FaceGoodLogic();
		$return  = $faceGoodLogic->getGoodList(40, $id);
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

			$return = $faceGoodLogic->insertGoodInfo($good['fc_url'], $good['fc_site'], $good['fc_id'], 0);
			echo $good['fc_url'].PHP_EOL;
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;
			$isSuccess = $return['code'] == 1000 ? 1 : ($return['code'] == -1 ? 3 : 2);

			//同步更新采集状态
			$return = $faceGoodLogic->updateGoodInfo($good['fc_id'], ['fc_status'=>$isSuccess,'fc_modify'=>date('Y-m-d H:i:s')]);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;

			Redis::del($urlMd5);
		}

		echo '脚本执行完成'.PHP_EOL;
    }

    public function test() {
    	//$url = 'https://www.soulmiacollection.com/mock-collar-cow-print-shift-product50727.html';
    	$url = 'https://www.ageluville.com/collections/pants/products/casual-lightning-print-joggers';
		$faceGoodLogic = new FaceGoodLogic();
		$return  = $faceGoodLogic->insertGoodInfo($url, 500, 0, 0);
		dump($return);
		die;
	}

}
