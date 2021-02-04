<?php

namespace App\Console\Commands;

use App\Logics\FaceCollectLogic;
use App\Logics\FaceGoodLogic;
use Illuminate\Support\Facades\Redis;


class FaceCollectCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faceCollect:cai {--id=} {--debug=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集专辑对应商品信息 ';

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
		$faceCollectLogic = new FaceCollectLogic();
		$return  = $faceCollectLogic->getCollectList(1, $id);
		if(empty($return['data'])) {
			echo '没有要采集的数据'.PHP_EOL;
			return false;
		}

		foreach($return['data'] as $collect) {
			$urlMd5 = md5($collect['fcc_url']);
			if(Redis::exists($urlMd5)) {
				continue;
			}

			Redis::set($urlMd5, 1);
			Redis::expire($urlMd5, 300);

			$return = $faceCollectLogic->getGoodUrlByCollectUrl($collect['fcc_url']);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;

			//添加采集到的商品链接
			$return = $faceCollectLogic->insertGoodUrl($collect['fcc_id'], $return['data']);
			echo json_encode($return, JSON_UNESCAPED_UNICODE).PHP_EOL;

			Redis::del($urlMd5);
		}

		echo '脚本执行完成'.PHP_EOL;
    }


    public function  test() {
		$faceCollectLogic = new FaceCollectLogic();
		$url = "https://shecherry.com/collections/womens-tee";
		$return = $faceCollectLogic->getGoodUrlByCollectUrl($url);
		dump($return);die;
	}

}
