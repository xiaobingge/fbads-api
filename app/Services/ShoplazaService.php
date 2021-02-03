<?php
namespace App\Services;
use App\Logics\FaceGoodLogic;
use Illuminate\Support\Facades\Redis;
use App\Models\FaceGoodsRs;
use App\Models\FaceGoods;
use App\Models\FaceGoodsImage;
use App\Models\FaceGoodsOption;
use App\Models\FaceGoodsSku;
use Intervention\Image\ImageManager;

/**
 * Class ShoplazaLogic Shoplaza
 */
class ShoplazaService
{

//https://zalikera.myshoplaza.com/admin
//域名：www.zalikera.com/
//token: Ms-oWl0O9m3lJJBhexQqSr1P2YHMeT2oiFnnWv-nWl8
//App UID:3Svnd4RAjNFF1vWJzMFfxwoWZMSR5uoTJWpzBp5Ar74
//App Secret:Vxlo1OgGsQstccIrdrb40gCX75RpXWVjk9JnCC0n3qo

// 店铺自带域名：https://uedress.myshoplaza.com/
// 绑定域名：uedress.com
//token:5M7sTI-1LKrGXSbVSqndgBzc9SoXdX4JCbBcPmOvuVM
//app uid:i7rX09euDgIvk0GK4TvP3w-WoZrRRnJgf4A2OM9KK-A
//app secret:sP5RbQHCjN_CpEl2o3NrFZUmyGgEhJUNkADMQaYhdrY

//https://lumylus8.myshoplaza.com/admin
//token：nDOnuFU8UQQPUgUqbFYvuh1bJwx_Q7SQxMwV0rtPQik
//app uid ：IR-ZUrqNOSgreSw34pLKSrdpthtllD9DtB9Y5QD41w8
//App Secret： UEH3XmUTIDHZwBJdRhVkRDM9xh-nniJcsefVSVdD8A4

//https://sharelily.myshoplaza.com/
//token:XFY1UXoxqZz9sp77fOJ7snHk9a0GyuGt2iNE4SwIJFY
//appuid:CEixqo3cQb0XeyfwQob5lDTHzg0LCd94-HX9USQLAl8
//app secret:7dyuCT3ToLq9_U59NhS7Qd5R877ke09IoS0S3akUCfY

//https://jeafly.myshoplaza.com/admin
//token:M8k3mCoGfCrm4h2tmrG9dmgoEJo7yPuGBCJ9bmHe3d4
//appuid:YsbyU1kGoiMJnN25ReDbjCKZVR4ApX1QZqQEA9c63pk
//app secret:xiLFQ3M_mJUC7KeauCSBF4y5GtF0UQ6aa63gZ7rSAhY

//https://ifashionfull.myshoplaza.com/
//token:O4Pzl2TP_tWhs3fnBlcIYHAo5BavV-cZOQnplUOULxw
//app uid:myWtnHc2dowLSr0pM0Uqg860SYlZyN1Kg6nQRywltic
//app secret: EEp2IJ3X7H8YmkQ8hsdsZPWYpdOwxkFW-166p1ZuzuM

//version:2020-01

    private $urlPre = [];

    private $accessToken = [];

    private $_shopifyBaseUrls = [];

    private $toolKeyUrl = [];

    protected $logPath = 'facebook/faceapi/';
    protected $logFileName;

    public function __construct()
    {
        $this->urlPre = app('config')->get('shopkeys.shoplaza_url');
        $this->accessToken = app('config')->get('shopkeys.shoplaza_token');
        $this->_shopifyBaseUrls = app('config')->get('shopkeys.shopify_url');
        $this->toolKeyUrl = app('config')->get('shopkeys.tool_key_url');

        $this->logFileName = strtolower(sprintf('%s/%s-%s.log', $this->logPath, 'faceapi', date('Y-m-d')));
    }

    // shoplaza http clients
    private static $_instance = array();

    // shopify http clients
    private static $_http_instance = array();

    public function getUrl($curr = 0)
    {
        return isset($this->urlPre[$curr]) ? $this->urlPre[$curr] : '';
    }

    public function getShopifyUrl($shop_key)
    {
        if (empty($shop_key)) {
            return '';
        }
        return isset($this->_shopifyBaseUrls[$shop_key]) ? $this->_shopifyBaseUrls[$shop_key] : '';
    }

    public function getToolKeys() {
        return array_keys($this->toolKeyUrl);
    }

    public function getToolUrl($key) {
        if (empty($key)) {
            return '';
        }
        return isset($this->toolKeyUrl[$key]) ? $this->toolKeyUrl[$key] : '';
    }

    /**
     * @param int $key
     * @param int $curr
     * @return \GuzzleHttp\Client
     */
    private function getClient($key = 0, $curr = 0)
    {
        $index = $key . '_' . $curr;
        if (!isset(static::$_instance[$index]) || !(static::$_instance[$index] instanceof \GuzzleHttp\Client)) {
            static::$_instance[$index] = new \GuzzleHttp\Client([
                // 'debug' => true,
                'timeout'  => 80,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Access-Token' => $this->accessToken[$curr]
                ],
                'verify' => false
            ]);
        }

        return static::$_instance[$index];
    }

    /**
     * @param int $key
     * @return \GuzzleHttp\Client
     */
    private function getShopifyClient($index = 0)
    {
        if (!isset(static::$_http_instance[$index]) || !(static::$_http_instance[$index] instanceof \GuzzleHttp\Client)) {
            static::$_http_instance[$index] = new \GuzzleHttp\Client([
                'timeout'  => 30,
            ]);
        }

        return static::$_http_instance[$index];
    }

    public function getToolFixMsg($key, $option = []) {
        $productId = $option['productId'];
        $title = $option['title'];
        $handle = $option['handle'];

        if (empty($productId) && empty($handle) && empty($title)) {
            return ['code' => 2001, 'msg' => 'productId, handle, title 必须有一个'];
        }

        $t = $this->getToolUrl($key);
        if (empty($t)) {
            return ['code' => 2002, 'msg' => '没有找到的站点，请选择'];
        }

        if ($t['type'] == 'shopify') {
            $url = $this->_shopifyBaseUrls[$t['index']];
            $client = $this->getShopifyClient($t['index']);
            if (!empty($productId)) {
                $firstUrl = sprintf("%s/admin/api/2021-01/products.json?limit=%d&fields=id,title,variants,created_at,updated_at,handle,image&ids=%s", $url, 70, $productId);
            } elseif (!empty($title)) {
                $firstUrl = sprintf("%s/admin/api/2021-01/products.json?limit=%d&fields=id,title,variants,created_at,updated_at,handle,image&title=%s", $url, 70, urlencode($title));
            } else {
                $firstUrl = sprintf("%s/admin/api/2021-01/products.json?limit=%d&fields=id,title,variants,created_at,updated_at,handle,image&handle=%s", $url, 70, urlencode($handle));
            }

            $res = $client->request('GET', $firstUrl)->getBody();
            $data = json_decode((string)$res, true);
            $product = array_first($data['products']);
            if (empty($product)) {
                return ['code' => 2002, 'msg' => '没有找到的商品'];
            }
            $errmsg = $this->fixShopifyData($url, $client, $product);
        } elseif ($t['type'] == 'shoplaza') {

            if (!empty($productId)) {
                $products = $this->getList(['ids' => $productId], $t['index'], 20, 0);
            } elseif (!empty($title)) {
                $products = $this->getList(['keyword' => $title], $t['index'], 20, 0);
            } else {
                return ['code' => 2001, 'msg' => 'shoplaza 目前仅支持 productId 查询'];
            }

            $product = array_first($products);
            if (empty($product)) {
                return ['code' => 2002, 'msg' => '没有找到的商品'];
            }
            $errmsg = $this->fixShoplazaData($t['index'], $product);
        }

        return ['code' => 1000, 'msg' => $errmsg];
    }

    public function fixShopifyData($url, $client, $product) {
        $productId = $product['id'];
        $err_msg = [];
        // 处理价格
        $variants = $product['variants'];
        if (count($variants) > 0) {
            foreach ($variants as $variant) {
                if (empty($variant['compare_at_price']) || $variant['compare_at_price'] == '0.00' || $variant['compare_at_price'] == '0' || $variant['compare_at_price'] <= 0) {
                    continue;
                }
                try {
                    $tmpRes = $client->request('PUT',
                        sprintf("%s/admin/api/2021-01/variants/%s.json", $url, $variant['id']),
                        [
                            'json' => ['variant' => ['id' => $variant['id'], 'compare_at_price' => null]]
                        ]
                    );
                    if ($tmpRes->getStatusCode() != 200) {
                        $err_msg[] =  'PUT: variant : ' . $variant['id'] . '-' . $tmpRes->getStatusCode() . '-' . $tmpRes->getBody()->getContents();
                    }
                }catch (\Exception $e) {
                    $err_msg[] = 'Exception: variant : ' . $variant['id'] . '-' . $e->getMessage();
                }
            }
        }

        // 处理图片
        $image = $product['image'];
        $imageNewUrl = $this->getResizeShopifyImage($image);
        if ($imageNewUrl !== false) {
            // 添加图片
            try {
                $tmpRes = $client->request('POST',
                    sprintf("%s/admin/api/2021-01/products/%s/images.json", $url, $productId), [
                        'json' => ['image' => $imageNewUrl],
                    ]);
                if ($tmpRes->getStatusCode() != 200) {
                    $err_msg[] =  'PUT: Product : ' . $productId . '-' . $tmpRes->getStatusCode() . '-' . $tmpRes->getBody()->getContents() . PHP_EOL;
                }
            }catch (\Exception $e) {
                $err_msg[] =  'Exception: Product : ' . $productId . '-' . $e->getMessage() . PHP_EOL;
            }
        }
        // 已处理过
        if ($productId != $product['handle']) {
            try {
                // 处理handle
                $tmpRes = $client->request('PUT', sprintf("%s/admin/api/2021-01/products/%s.json", $url, $productId), [
                    'json' => ['product' => ['handle' => $productId]]
                ]);
                if ($tmpRes->getStatusCode() != 200) {
                    $err_msg[] =  'PUT: Product : ' . $productId . '-' . $tmpRes->getStatusCode() . '-' . $tmpRes->getBody()->getContents() . PHP_EOL;
                }
            }catch (\Exception $e) {
                $err_msg[] =  'Exception: Product : ' . $productId . '-' . $e->getMessage() . PHP_EOL;
            }
        }

        return $err_msg;
    }

    public function fixShoplazaData($index, $product) {
        $imageUrl = $this->getResizeShoplazaImage($product['image']);
        if (!is_string($imageUrl)) {
            return $product['id'] . "合成图片失败";
        }
        $result = $this->createImage($product['id'], $index, $imageUrl, 1);
        if ($result == false) {
            return $product['id'] . "上传图片失败";
        }
    }


    public function getResizeShopifyImage($imageData) {
        $imageSrc = $imageData['src'];
        $imageW = $imageData['width'];
        $imageH = $imageData['height'];

        if (round($imageW * 100 / $imageH) > 75) {
            $imageNewW = round($imageH * 0.75);
            $imageNewH = $imageH;
        } elseif (round($imageW * 100 / $imageH) < 75) {
            $imageNewW = $imageW;
            $imageNewH = round($imageW * 100 / 75);
        } else {
            return false;
        }

        if ($imageNewW > 1500) { // 尺寸过大时
            $imageNewW = 1500;
            $imageNewH = 2000;
        }

        $fileName = $this->download($imageSrc, '/shopify', md5($imageSrc));
        if (false === $fileName) {
            return false;
        }
        $imageManager = new ImageManager();

        try {
            $image = $imageManager->make(\Storage::disk('public')->path($fileName));
            $image->fit($imageNewW, $imageNewH);
            $image->response('jpg');
            $attachment = base64_encode($image->encode('jpg'));
            \Storage::disk('public')->delete($fileName);
            if (empty($attachment)) {
                return false;
            }
            return ['position' => 1, 'attachment' => $attachment, 'filename' => $fileName];
        }catch (\Exception $e) {
        }
        return false;
    }

    public function getResizeShoplazaImage($imageData) {
        $imageSrc = $imageData['src'];
        $imageW = $imageData['width'];
        $imageH = $imageData['height'];

        if ($imageSrc == '//img.staticdj.com/c6d4207f5b6190aad3f445b8c6f71e8e.jpeg'|| $imageSrc == '//cdn.shoplazza.com/shirt_1080x.png'  || $imageData['path'] == 'loading.png' || $imageSrc == '//img.staticdj.com/loading.png') {
            return false;
        }

        if ($imageW > 0 && $imageH > 0) {
            if (round($imageW * 100 / $imageH) > 75) {
                $imageNewW = round($imageH * 0.75);
                $imageNewH = $imageH;
            } elseif (round($imageW * 100 / $imageH) < 75) {
                $imageNewW = $imageW;
                $imageNewH = round($imageW * 100 / 75);
            } else {
                return true;
            }
        }

        $fileName = $this->download($imageSrc, '/shoplaza', md5($imageSrc));
        $imageManager = new ImageManager();

        try {
            $image = $imageManager->make(\Storage::disk('public')->path($fileName));
            if ($imageW <= 0 || $imageH <=0) {
                $imageW = $image->width();
                $imageH =$image->height();

                if (round($imageW * 100 / $imageH) > 75) {
                    $imageNewW = round($imageH * 0.75);
                    $imageNewH = $imageH;
                } elseif (round($imageW * 100 / $imageH) < 75) {
                    $imageNewW = $imageW;
                    $imageNewH = round($imageW * 100 / 75);
                } else {
                    \Storage::disk('public')->delete($fileName);
                    return true;
                }
            }

            $image->fit($imageNewW, $imageNewH);
            $newImgPath = '/'.date('YmdH') . '/' . $fileName . "_new.jpg";
            // $image->save($newImg);
            $newImg = \Storage::disk('html')->put($newImgPath, $image->encode('jpg'));
            \Storage::disk('public')->delete($fileName);

            if ($newImg === false) {
                return false;
                // $this->logger("getResizeShoplazaImage error : " . $this->error);
            } else {
                return env('APP_URL') . '/tmp' . $newImgPath;
            }
        }catch (\Exception $e) {
            // $this->logger("getResizeShoplazaImage Exception : " . $e->getMessage());
        }
        return false;
    }

    /**
     * 下载远程图片
     */
    protected function download($url, $savePath, $identity, $useCache = true)
    {
        $filename = $savePath . '/' . $identity . '.jpg';
        // $this->logger(sprintf('DOWNLOAD:图片地址 %s', $url));
        $filenameX = \Storage::disk('public')->exists($filename);
        if ($filenameX && $useCache) {
            // $this->logger(sprintf('DOWNLOAD:使用缓存图片 %s', $filename));
            return $filename;
        }
        // $start = microtime(true) * 1000;
        try {
            $stream = $this->getClient(5)->get($url);
            if ($stream->getStatusCode() == 200) {
                \Storage::disk('public')->put($filename, $stream->getBody()->getContents());
            } else {
                return false;
            }
            //$end = microtime(true) * 1000;
            //$this->logger(sprintf('DOWNLOAD:完成 %s %s', $filename, ($end - $start)));

            return $filename;
        }catch (\Exception $e) {
            // $this->logger(sprintf('DOWNLOAD:失败 %s', $e->getMessage()));
        }
    }



    public function getCommandFixFilter($key, $filter, $option = []) {
        $productId = $option['productId'];
        $title = $option['title'];
        $handle = $option['handle'];

        $where = [];

        $t = $this->getToolUrl($key);
        if (empty($t)) {
            return ['code' => 2002, 'msg' => '没有找到的站点，请选择'];
        }

        if ($t['type'] == 'shopify') {
            $url = $this->_shopifyBaseUrls[$t['index']];
            $client = $this->getShopifyClient($t['index']);
            $where = [];
            if (!empty($product)) {
                $where['ids'] = $productId;
            }
            if (!empty($title)) {
                $where['keyword'] = $title;
            }
            if (!empty($handle)) {
                $where['handle'] = $handle;
            }

            $firstUrl = sprintf("%s/admin/api/2021-01/products.json?limit=%d&fields=id,title,body_html,tags&%s", $url, 70, http_build_query($where));
            $this->getCommandFixFilterShopify($client, $url, $firstUrl, $filter);

        } elseif ($t['type'] == 'shoplaza') {
            if (!empty($product)) {
                $where['ids'] = $productId;
            }
            if (!empty($title)) {
                $where['keyword'] = $title;
            }
            $count = $this->getCount($where, $t['index']);
            $pageSize = 20;
            $page = 0;
            $maxPage = ceil($count / $pageSize);

            $tmpKey = 'com.juanpi.shell7.shoplaza.'  . $t['index'];
            $redis = Redis::connection();

            do {
                $products = $this->getList($where, $t['index'], $pageSize, $page, ['id', 'title', 'handle', 'description']);
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $productId = $product['id'];
                        if ($redis->hexists($tmpKey, $productId)) {
                            continue;
                        }

                        $postData = [];

                        $title = str_replace($filter, '', $product['title']);
                        if ($title != $product['title']) {
                            $postData['title'] = $title;
                        }

                        $description = str_replace($filter, '', $product['description']);
                        if ($description != $product['description']) {
                            $postData['description'] = $description;
                        }

                        $handle = str_replace($filter, '', $product['handle']);
                        if ($handle != $product['handle']) {
                            $postData['handle'] = $handle;
                        }

                        if (empty($postData)) {
                            $redis->hSet($tmpKey, $productId, 1);
                            $redis->expire($tmpKey, 864000); // 10天
                            continue;
                        }

                        echo 'Shoplaza Product : ' . $productId . '需要修改' . implode(',', array_keys($postData)) . PHP_EOL;
                        mLog($this->logFileName, $productId. '修改的数据' . json_encode($postData));

                        $tmpRes = $this->updateData($productId, $t['index'], $postData);
                        if (false === $tmpRes) {
                            $redis->hSet($tmpKey . '.error', $productId, 1);
                            $redis->expire($tmpKey . '.error', 864000); // 10天
                            echo 'Shoplaza Product : ' . $productId . PHP_EOL;
                        }

                        $redis->hSet($tmpKey, $productId, 1);
                        $redis->expire($tmpKey, 864000); // 10天
                    }
                }
                $page++;
            } while ($page <= $maxPage);
        }

    }


    private function getCommandFixFilterShopify($client, $baseUrl, $url, $filter)
    {
        echo date('c') . "|type:|".$filter."|url:".$url . PHP_EOL;
        $res = $client->get($url);
        $resHeaderLink = $res->getHeader("link");
        $nextPageUrl = "";

        if (mb_strlen($resHeaderLink[0]) > 0) {
            $resHeaderLinkArr = explode(",", $resHeaderLink[0]);
            if (count($resHeaderLinkArr) > 1) {
                $item = trim($resHeaderLinkArr[1]);
            } else {
                $item = trim($resHeaderLinkArr[0]);
            }
            $itemArr = explode(";", $item);
            if (trim($itemArr[1]) == "rel=\"next\"") {
                $nextPageUrl = mb_strcut($itemArr[0], 1, mb_strlen($itemArr[0]) - 2);
                $baseUrlArr = explode('@', $baseUrl);
                $nextPageUrl = str_replace('https://' . $baseUrlArr[1], $baseUrl, $nextPageUrl);
            }
        }
        echo "date : " . date('c') . " NextPageUrl : " . $nextPageUrl . PHP_EOL;
        $content = json_decode($res->getBody(), true);
        $md5BaseUrl = md5($baseUrl);
        $tmpKey = 'com.juanpi.shell7.shopify.'  . $md5BaseUrl;
        $redis = Redis::connection();
        foreach ($content['products'] as $product) {
            $productId = $product['id'];
            // 已处理过
            if ($redis->hexists($tmpKey, $productId)) {
                continue;
            }

            $postData = [];

            $title = str_replace($filter, '', $product['title']);
            if ($title != $product['title']) {
                $postData['title'] = $title;
            }

            $body_html = str_replace($filter, '', $product['body_html']);
            if ($body_html != $product['body_html']) {
                $postData['body_html'] = $body_html;
            }

            $tags = str_replace($filter, '', $product['tags']);
            if ($tags != $product['tags']) {
                $tags = array_filter(explode(',', $tags));
                $postData['tags'] = implode(',', $tags);
            }

            if (empty($postData)) {
                $redis->hSet($tmpKey, $productId, 1);
                $redis->expire($tmpKey, 864000); // 10天
                continue;
            }
            echo 'Shopify Product : ' . $productId . '需要修改' . implode(',', array_keys($postData)) . PHP_EOL;
            mLog($this->logFileName, $productId. '修改的数据' . json_encode($postData));

            try {
                // 处理handle
                $tmpRes = $client->request('PUT', sprintf("%s/admin/api/2021-01/products/%s.json", $baseUrl, $productId), [
                    'json' => ['product' => $postData]
                ]);
                if ($tmpRes->getStatusCode() != 200) {
                    $redis->hSet($tmpKey . '.error', $productId, 1);
                    $redis->expire($tmpKey . '.error', 864000); // 10天
                    echo 'Product : ' . $productId . '-' . $tmpRes->getStatusCode() . '-' . $tmpRes->getBody()->getContents() . PHP_EOL;
                }
                $redis->hSet($tmpKey, $productId, 1);
                $redis->expire($tmpKey, 864000); // 10天
            }catch (\Exception $e) {
                echo 'Product : ' . $productId . '-' . $e->getMessage() . PHP_EOL;
            }
        }

        if (!empty($nextPageUrl)) {
            usleep(500);
            $this->getCommandFixFilterShopify($client, $baseUrl, $nextPageUrl, $filter);
        }
    }



    /**
     * 商品总数量
     * @param $where
     * @param int $curr
     * @return bool|int|mixed
     */
    public function getCount($where, $curr = 0)
    {
        try {
            $url = $this->urlPre[$curr] . '/openapi/2020-01/products/count?' . http_build_query($where);

            $res = $this->getClient(10, $curr)->request('GET', $url);
            $countData = json_decode((string)$res->getBody(), true);
            return isset($countData['count']) ? $countData['count'] : 0;
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
        }
        return false;
    }



	/**
     * 商品列表
     *
     * @param $where
     * @param int $limit
     * @param int $page
     * @param int $curr
     * @param array $fields
     * @return bool|mixed|null
     */
    public function getList($where, $curr = 0, $limit = 20, $page = 1, $fields = [])
    {
        if (empty($fields)) {
            $fields = ['id','title','created_at','updated_at','handle','need_variant_image','image','images','variants'];
        }
        $url = $this->urlPre[$curr] . '/openapi/2020-01/products.json?fields='. implode(',', $fields) .'&limit='.$limit.'&page=' . $page . '&' . http_build_query($where);
        try {
            $res = $this->getClient(11, $curr)->request('GET', $url);
            $data = json_decode((string)$res->getBody(), true);
            return isset($data['products']) ? $data['products'] : null;
        } catch (\Exception $e) {
            mLog($this->logFileName, $e->getMessage());
        }
        return false;
    }

	/**
	 * 专辑总数量
	 * @param $where
	 * @param int $curr
	 * @return bool|int|mixed
	 */
	public function getCollectCount($where, $curr = 0)
	{
		try {
			$url = $this->urlPre[$curr] . '/openapi/2020-01/collections/count?' . http_build_query($where);
			$res = $this->getClient(10, $curr)->request('GET', $url);
			$countData = json_decode((string)$res->getBody(), true);
			return isset($countData['count']) ? $countData['count'] : 0;
		} catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}


	/**
	 * 专辑列表
	 * @param $where
	 * @param int $curr
	 * @return bool|int|mixed
	 */
	public function getCollectList($where, $curr = 0, $limit = 20, $page = 1)
	{
		$url = $this->urlPre[$curr] . '/openapi/2020-01/collections?limit='.$limit.'&page=' . $page . '&' . http_build_query($where);
		try {
			$res = $this->getClient(11, $curr)->request('GET', $url);
			$data = json_decode((string)$res->getBody(), true);
			return isset($data['collections']) ? $data['collections'] : null;
		} catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}

	public function getShopifyCollectCount($where, $shopId) {
		try {
			$url = $this->_shopifyBaseUrls[$shopId] . '/admin/api/2020-10/smart_collections/count.json?' . http_build_query($where);
			$res = $this->getClient()->get($url);
			$countData = json_decode($res->getBody(), true);
			return isset($countData['count']) ? $countData['count'] : 0;
		} catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}

	/**
	 * 专辑列表
	 * @param $where
	 * @param int $curr
	 * @return bool|int|mixed
	 */
	public function getShopifyCollectList($where, $shopId = 0, $limit = 20, $nextUrl='') {
		try {
			if(empty($nextUrl)) {
				$url = $this->_shopifyBaseUrls[$shopId] . '/admin/api/2020-10/smart_collections.json?limit='.$limit. '&' . http_build_query($where);
			}else{
				$url = $nextUrl;
			}
			$res = $this->getClient()->request('GET', $url);
			$data = json_decode((string)$res->getBody(), true);
			if(!isset($data['smart_collections'])) {
				return false;
			}

			$nextPageUrl = '';
			$resHeaderLink = $res->getHeader("link");
			if (mb_strlen($resHeaderLink[0]) > 0) {
				$resHeaderLinkArr = explode(",", $resHeaderLink[0]);
				if (count($resHeaderLinkArr) > 1) {
					$item = trim($resHeaderLinkArr[1]);
				} else {
					$item = trim($resHeaderLinkArr[0]);
				}
				$itemArr = explode(";", $item);
				if (trim($itemArr[1]) == "rel=\"next\"") {
					$nextPageUrl = mb_strcut($itemArr[0], 1, mb_strlen($itemArr[0]) - 2);
					$baseUrlArr = explode('@', $this->_shopifyBaseUrls[$shopId]);
					$nextPageUrl = str_replace('https://' . $baseUrlArr[1], $this->_shopifyBaseUrls[$shopId], $nextPageUrl);
				}
			}

			return ['collect_list'=>$data['smart_collections'], 'next_page_url'=>$nextPageUrl];
		} catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}


	/**
	 * 专辑商品数量
	 * @param $where
	 * @param int $shopId
	 * @return bool|int|mixed
	 */
	public function getShopifyGoodCount($where, $shopId) {
		try {
			$url = $this->_shopifyBaseUrls[$shopId] . '/admin/api/2020-10/products/count.json?' . http_build_query($where);
			$res = $this->getClient()->request('GET', $url);
			$countData = json_decode((string)$res->getBody(), true);
			return isset($countData['count']) ? $countData['count'] : 0;
		} catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}

	/**
	 * 专辑商品信息
	 * @param $where
	 * @param int $curr
	 * @return bool|int|mixed
	 */
	public function getShopifyGoodList($where, $shopId = 0, $limit = 20, $nextUrl='') {
		try {
			if(empty($nextUrl)) {
				$fields = 'id,title,variants,tags,created_at,updated_at,handle,image,images,vendor,vendor_url,product_id,published_at';
				$url = $this->_shopifyBaseUrls[$shopId] . '/admin/api/2020-10/products.json?limit='.$limit. '&fields='.$fields.'&' . http_build_query($where);
			}else{
				$url = $nextUrl;
			}
			$res = $this->getClient()->request('GET', $url);
			$data = json_decode((string)$res->getBody(), true);
			if(!isset($data['products'])) {
				return false;
			}

			$nextPageUrl = '';
			$resHeaderLink = $res->getHeader("link");
			if (mb_strlen($resHeaderLink[0]) > 0) {
				$resHeaderLinkArr = explode(",", $resHeaderLink[0]);
				if (count($resHeaderLinkArr) > 1) {
					$item = trim($resHeaderLinkArr[1]);
				} else {
					$item = trim($resHeaderLinkArr[0]);
				}
				$itemArr = explode(";", $item);
				if (trim($itemArr[1]) == "rel=\"next\"") {
					$nextPageUrl = mb_strcut($itemArr[0], 1, mb_strlen($itemArr[0]) - 2);
					$baseUrlArr = explode('@', $this->_shopifyBaseUrls[$shopId]);
					$nextPageUrl = str_replace('https://' . $baseUrlArr[1], $this->_shopifyBaseUrls[$shopId], $nextPageUrl);
				}
			}

			return ['products'=>$data['products'], 'next_page_url'=>$nextPageUrl];
		} catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}





	/**
     * 商品详情
     *
     * @param $productId
     * @param int $curr
     * @return bool|mixed|null
     */
    public function getDetail($productId, $curr = 0)
    {
        $url = $this->urlPre[$curr] . '/openapi/2020-01/products/' . $productId;

        try {
            $res = $this->getClient(11, $curr)->request('GET', $url);
            $data = json_decode((string)$res->getBody(), true);
            return isset($data['product']) ? $data['product'] : null;
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return false;
    }

	/**
	 * 商品详情
	 *
	 * @param $productId
	 * @param int $curr
	 * @return bool|mixed|null
	 */
	public function getShopifyDetail($productId, $shopId = 0)
	{
		$url = $this->_shopifyBaseUrls[$shopId] . '/admin/api/2020-10/products/'.$productId.'.json';
		try {
			$res = $this->getClient(11, $shopId)->request('GET', $url);
			$data = json_decode((string)$res->getBody(), true);
			return isset($data['product']) ? $data['product'] : null;
		} catch (\Exception $e) {
		   mLog($this->logFileName, $e->getMessage());
		}
		return false;
	}

    /**
     * 更新商品信息
     *
     * @param string $productId
     * @param int $curr
     * @param array $data
     * @return bool|mixed
     */
    public function updateData($productId, $curr = 0, $data = [])
    {
        if (empty($productId) || empty($data)) {
            return false;
        }

        $url = $this->urlPre[$curr] . '/openapi/2020-01/products/' . $productId;
        try {
            $res = $this->getClient(12, $curr)->request('PUT', $url, ['json' => $data]);
            $data = json_decode((string)$res->getBody(), true);
            return $data;
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return false;
    }

    /**
     * 更新子商品信息
     *
     * @param string $variantId
     * @param int $curr
     * @param array $data
     * @return bool|mixed
     */
    public function updateVariantData($variantId, $curr = 0, $data = [])
    {
        if (empty($variantId) || empty($data)) {
            return false;
        }
        $url = $this->urlPre[$curr] . '/openapi/2020-01/variants/'.$variantId;

        try {
            $res = $this->getClient(12, $curr)->request('PUT', $url, ['json' => $data]);
            $data = json_decode((string)$res->getBody(), true);
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return $data ?: false;
    }

    /***
     * 创建商品图片
     *
     * @param string $productId
     * @param int $curr
     * @param string $imageSrc
     * @param int $pos
     * @return bool|mixed
     */
    public function createImage($productId, $curr = 0, $imageSrc = '', $pos = 1)
    {
        if (empty($productId) || empty($imageSrc)) {
            return false;
        }
        $url = $this->urlPre[$curr] . '/openapi/2020-01/products/' . $productId . '/images';
        $data = [
            'image' => [
                'position' => $pos,
                'src' => $imageSrc
            ]
        ];
        try {
            $res = $this->getClient(12, $curr)->request('POST', $url, ['json' => $data]);
            $result = json_decode((string)$res->getBody(), true);
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return $result ?: false;
    }

    /***
     * 删除商品图片
     *
     * @param string $productId
     * @param int $curr
     * @param string $imageId
     * @param int $pos
     * @return bool|mixed
     */
    public function deleteImage($productId, $curr = 0, $imageId = '')
    {
        if (empty($productId) || empty($imageId)) {
            return false;
        }
        $url = $this->urlPre[$curr] . '/openapi/2020-01/products/' . $productId . '/images/' . $imageId;
        echo 'deleteImage : ' . $url . PHP_EOL;
        try {
            $res = $this->getClient(12, $curr)->request('DELETE', $url);
            $result = json_decode((string)$res->getBody(), true);
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return $result ?: false;
    }


    public function orders_count($where, $curr = 0)
    {
        $url = $this->urlPre[$curr] . '/openapi/2020-01/orders/count?' . http_build_query($where);
        echo $url . PHP_EOL;
        try {
            $res = $this->getClient(13, $curr)->request('GET', $url);

            $data = json_decode((string)$res->getBody(), true);
            return isset($data['count']) ? $data['count'] : 0;
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return false;
    }

    public function orders($where, $curr = 0, $limit = 20, $page = 1)
    {
        $url = $this->urlPre[$curr] . '/openapi/2020-01/orders?limit='.$limit. '&page=' . $page . '&' . http_build_query($where);
        try {
            $res = $this->getClient(13, $curr)->request('GET', $url);

            $data = json_decode((string)$res->getBody(), true);
            return isset($data['orders']) ? $data['orders'] : null;
        } catch (\Exception $e) {
			mLog($this->logFileName, $e->getMessage());
		}
        return false;
    }

    /**
     * 更新商品
     *
     * @param $shopKey
     * @param $productId
     * @param $data
     */
    public function putShopifyGoodsBase($shopKey, $productId, $data) {
        $shopify_web = $this->getShopifyUrl($shopKey);
        if (empty($shopify_web) || empty($data)) {
            return false;
        }

        $url = $shopify_web . '/admin/api/2021-01/products/'.$productId.'.json';
        try {
            $res = $this->getShopifyClient(20)->request('POST', $url, ['json' => ['product' => $data]]);
            $result = json_decode((string)$res->getBody(), true);
            if ($result['product']['id'] == $productId) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * 创建shopify商品
     * @param int   $shop_key  需要同步的站点id
     * @param array $products  需要同步的商品id
     *
     * @return
     */
    public function createshopifygoods($shop_key, $products = [])
    {
        $shopify_web = $this->getShopifyUrl($shop_key);
        if (empty($shopify_web)) {
            return false;
        }
        $shoplaza_key = 'redis_goods_list_shopify_' . $shop_key;
        $shoplaza_faile_key = 'redis_goods_list_faile_shopify_list';
        // 失败次数
        $shoplaza_faile_hash = 'redis_goods_list_faile_shopify_hash';
        $shoplaza_max_key = 'redis_goods_max_shopify_' . $shop_key;
        $redis = Redis::connection('default');
        if (empty($products)) {
            $product_id_count = $redis->llen($shoplaza_key);
            if (empty($product_id_count)) {
                $max_goods_id = $redis->get($shoplaza_max_key);
                $max_goods_id = empty($max_goods_id) ? 0 : $max_goods_id;
                $goods_list = FaceGoods::where('id' , '>', $max_goods_id)->get();
                if (empty($goods_list)) {
                    return;
                }
                foreach ($goods_list as $info) {
                    $redis->rPush($shoplaza_key, $info->product_id);
                    $max_id = $info->id;
                }
                $redis->set($shoplaza_max_key, $max_id);
            }
        }
        $cnt_success = 0; $cnt_faile = 0;

        // 上次错误写入队列
        $shoplaza_faile_len = $redis->llen($shoplaza_faile_key);
        if ($shoplaza_faile_len > 0) {
            do {
                $tmp_p = $redis->lpop($shoplaza_faile_key);
                if (empty($tmp_p)) {
                    break;
                }
                $redis->rPush($shoplaza_key, $tmp_p);
            } while (true);
        }

        // 需要过滤的关键字
        $filterKey = app(FaceGoodLogic::class)->getFilterKeyArr();

        while (true) {
            if (!empty($products)) {
                $product_id = array_pop($products);
            } else {
                $product_id = $redis->lpop($shoplaza_key);
            }

            if (empty($product_id)) {
                break;
            }

            $fail_try_cnt = $redis->hget($shoplaza_faile_hash, $product_id);
            if ($fail_try_cnt >= 3) {
                // 已经失败3次了，放弃治疗;
                mLog($this->logFileName.'_fail.txt', $product_id . 'upload falid : try num : ' . $fail_try_cnt);
                continue;
            }

            //首先查找是否已经上传到对应网站
            if(FaceGoodsRs::where('shop_index', $shopify_web)->where('resource_product_id', $product_id)->exists()){
                continue;
            }
            $goods_info = FaceGoods::where('product_id', $product_id)->first();
            if (empty($goods_info)) {
                continue;
            }

            $postData = [
                'title' => str_replace($filterKey, '', $goods_info->title),
                'published' => false,
                'vendor' => $goods_info->vendor,
                'handle' => $goods_info->handle,
                'status' => 'active',
            ];

            if (!empty($goods_info->content)) {
                $postData['body_html'] = str_replace($filterKey, '', htmlspecialchars_decode($goods_info->content));
            } elseif (!empty($goods_info->description)) {
                $postData['body_html'] = str_replace($filterKey, '', htmlspecialchars_decode($goods_info->description));
            } else {
                $postData['body_html'] = '';
            }

            $image_list = FaceGoodsImage::where('product_id', $product_id)->get();
            $images = [];
            // $images_more = [];
            foreach ($image_list as $imageinfo) {
                if (empty($imageinfo->src)) {
                    continue;
                }
                $imageSrc = $imageinfo->src;
                if (str_starts_with($imageSrc, '//')) {
                    $imageSrc = 'https:' . $imageSrc;
                }

                $images[] = ['src' => $imageSrc];
                // $images_more[] = ['src' => $imageSrc, 'variant_ids' => json_decode($imageinfo->variant_ids, true)];
            }
            // $data['product'];
            if (!empty($images)) {
                $postData['images'] = $images;
            }

            $sku_list = FaceGoodsSku::where('product_id', $product_id)->get();

            $variants = [];
            $variantOptions1 = [];
            $variantOptions2 = [];
            $variantOptions3 = [];
            foreach ($sku_list as $sk => $skuinfo) {
                $variant = [
                    'price' => (double)$skuinfo->price,
                    'sku' => $skuinfo->sku,
                    'inventory_quantity' => (int)$skuinfo->inventory_quantity,
                ];

                if (!empty($skuinfo->title)) {
                    $variant['title'] = str_replace($filterKey, '', $skuinfo->title);
                } else {
                    $option_array = array_filter([$skuinfo->option1, $skuinfo->option2, $skuinfo->option3]);
                    if (!empty($option_array)) {
                        $variant['title'] = implode(" ", $option_array);
                    }
                }


                if (!empty($skuinfo->inventory_policy)) {
                    $variant['inventory_policy'] = $skuinfo->inventory_policy;
                }

//                if (!empty($skuinfo->compare_at_price)) {
//                    $variant['compare_at_price'] = (double)$skuinfo->compare_at_price;
//                }

                // 重量
                if (!empty($skuinfo->grams)) {
                    $variant['grams'] = $skuinfo->grams;
                }

                if (!empty($skuinfo->option1)) {
                    $variant['option1'] = $skuinfo->option1;
                    $variantOptions1[] = $skuinfo->option1;
                }
                if (!empty($skuinfo->option2)) {
                    $variant['option2'] = $skuinfo->option2;
                    $variantOptions2[] = $skuinfo->option2;
                }
                if (!empty($skuinfo->option3)) {
                    $variant['option3'] = $skuinfo->option3;
                    $variantOptions3[] = $skuinfo->option3;
                }

                if (!empty($skuinfo->tax_code)) {
                    $variant['tax_code'] = $skuinfo->tax_code;
                }
                if (!empty($skuinfo->barcode)) {
                    $variant['barcode'] = $skuinfo->barcode;
                }
                if (!empty($skuinfo->weight)) {
                    $variant['weight'] = (double)$skuinfo->weight;
                }
                if (!empty($skuinfo->weigh_unit)) {
                    $variant['weigh_unit'] = $skuinfo->weight_unit;
                }
                $variants[] = $variant;
            }

            if (!empty($variants)) {
                $postData['variants'] = $variants;
            }


            $option_list = FaceGoodsOption::where('product_id', $product_id)->get();
            if ($option_list->isNotEmpty()) {
                foreach ($option_list as $option) {
                    $postData['options'][] = [
                        'name' => $option->name,
                        'values' => json_decode($option->values, true),
                        'position' => $option->position
                    ];
                }
            } else {
                $variantOptions1 = array_values(array_unique(array_filter($variantOptions1)));
                if (!empty($variantOptions1)) {
                    $postData['options'][] = [
                        'name' => 'Color',
                        'values' => $variantOptions1,
                        'position' => 1
                    ];
                }
                $variantOptions2 = array_values(array_unique(array_filter($variantOptions2)));
                if (!empty($variantOptions2)) {
                    $postData['options'][] = [
                        'name' => 'Size',
                        'values' => $variantOptions2,
                        'position' => 2
                    ];
                }
                // TODO ??
            }

            $url = $shopify_web . '/admin/api/2021-01/products.json';
            try {
                $res = $this->getShopifyClient(20)->request('POST', $url, ['json' => ['product' => $postData]]);
                $result = json_decode((string)$res->getBody(), true);
                mLog($this->logFileName, $result['product']['id'] . '-' . $result['product']['handle']);
                // 入库，写关联关系
                FaceGoodsRs::firstOrCreate([
                    'resource_product_id' => $product_id,
                    'product_id' => $result['product']['id'],
                    'shop_type' => 'shopify',
                    'type' => $shop_key,
                    'shop_index' => $shopify_web
                ]);
                $redis->hdel($shoplaza_faile_hash, $product_id);
                // $this->updateImage($result['product'],$sku_list,$image_list);
                $cnt_success ++;
            } catch (\Exception $e) {
                if ($fail_try_cnt < 3) {
                    // 如果重试少于3次 ，写入失败队列正
                    $redis->rPush($shoplaza_faile_key, $product_id);
                }
                $redis->hincrby($shoplaza_faile_hash, $product_id, 1);
                mLog($this->logFileName, $product_id.'========='.$e->getMessage());
                $cnt_faile ++;
                continue;
            }
            sleep(5);

        }

        return ['success' => $cnt_success, 'failed' => $cnt_faile];
    }

}