<?php

namespace App\Console\Commands;

use App\Http\Controllers\Auth\LoginController;
use App\Logics\FaceGoodLogic;

class FaceGoodCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'FaceGood:index {--id=} ';

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
		$faceGoodLogic = new FaceGoodLogic();
		$return  = $faceGoodLogic->getGoodList(1000);
		if(empty($return['data'])) {
			echo '没有要采集的数据'.PHP_EOL;
			return false;
		}

		foreach($return['data'] as $good) {
			$isSuccess = 2;
			//$url = 'https://www.sonoup.com/products/casual-solid-floral-printed-long-sleeved-sweatershirt';
			$return = $faceGoodLogic->insertGoodInfo($good['url'], $good['site'], 0);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;

			if($return['code'] == 1000) {
				$isSuccess = 1;
			}
			//同步更新采集状态
			$return = $faceGoodLogic->updateGoodInfo($good['id'], $isSuccess);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;
		}

		echo '脚本执行完成'.PHP_EOL;

    }

}
