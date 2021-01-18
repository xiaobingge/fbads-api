<?php
namespace App\Services;
use Illuminate\Support\Facades\Redis;
use App\Models\FaceGoodsRs;
use App\Models\FaceGoods;
use App\Models\FaceGoodsImage;
use App\Models\FaceGoodsOption;
use App\Models\FaceGoodsSku;

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

    private $urlPre = [
        0 => 'https://uedress.myshoplaza.com',
        1 => 'https://zalikera.myshoplaza.com',
        2 => 'https://lumylus8.myshoplaza.com',
        3 => 'https://sharelily.myshoplaza.com',
        4 => 'https://jeafly.myshoplaza.com',
        5 => 'https://ifashionfull.myshoplaza.com',
    ];

    private $accessToken = [
        0 => '5M7sTI-1LKrGXSbVSqndgBzc9SoXdX4JCbBcPmOvuVM',
        1 => 'Ms-oWl0O9m3lJJBhexQqSr1P2YHMeT2oiFnnWv-nWl8',
        2 => 'nDOnuFU8UQQPUgUqbFYvuh1bJwx_Q7SQxMwV0rtPQik',
        3 => 'XFY1UXoxqZz9sp77fOJ7snHk9a0GyuGt2iNE4SwIJFY',
        4 => 'M8k3mCoGfCrm4h2tmrG9dmgoEJo7yPuGBCJ9bmHe3d4',
        5 => 'O4Pzl2TP_tWhs3fnBlcIYHAo5BavV-cZOQnplUOULxw',
    ];

	private $_shopifyBaseUrls = [
		// 'https://2b68a91adcec217348f4e60e87b68c30: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@hinewdesign.myshopify.com',
		200 => 'https://7e032d2fadf9443f371252d4a2201e9a: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@silatila.myshopify.com',
		201 => 'https://0986db6ba79e860f95355930ffb41d0b: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@hiwardrobe.myshopify.com',
		//'https://355dde9b4cab7d35143f43f05c4080d3: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@dressestide.myshopify.com',
		//'https://e9913e871becda6b8f080b1d456bf235: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@lumylus.myshopify.com',
		202 => 'https://646603cddc739f3e7ab6e09cac63645f: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@ifashionfull.myshopify.com',
		//'https://97e201a4024c6af6761924c02e344392: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@zalikera.myshopify.com'
		204 => 'https://cb2c08074f39a816e264599ea500f013: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@hercoco.myshopify.com',
		205 => 'https://bba444d6b2290ea445466b8715963846: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@shecherry.myshopify.com'
	];

    protected $logPath = 'facebook/faceapi/';
    protected $logFileName;

    public function __construct()
    {
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
     * @return bool|mixed|null
     */
    public function getList($where, $curr = 0, $limit = 20, $page = 1)
    {
        $url = $this->urlPre[$curr] . '/openapi/2020-01/products.json?fields=id,title,created_at,updated_at,handle,need_variant_image,image,images,variants&limit='.$limit.'&page=' . $page . '&' . http_build_query($where);
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

        while (true) {
            if (!empty($products)) {
                $product_id = array_pop($products);
            } else {
                $product_id = $redis->lpop($shoplaza_key);
            }

            if (empty($product_id)) {
                break;
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
                'title' => $goods_info->title,
                'body_html' => $goods_info->description ? htmlspecialchars_decode($goods_info->description) : '',
                'published' => false,
                'vendor' => $goods_info->vendor,
                'handle' => $goods_info->handle,
                'status' => 'active',
            ];

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
                    $variant['title'] = $skuinfo->title;
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
                echo $result['product']['id'] . '-' . $result['product']['handle'] . PHP_EOL;
                mLog($this->logFileName, print_r($result, true));
                // 入库，写关联关系
                FaceGoodsRs::firstOrCreate([
                    'resource_product_id' => $product_id,
                    'product_id' => $result['product']['id'],
                    'shop_type' => 'shopify',
                    'type' => $shop_key,
                    'shop_index' => $shopify_web
                ]);
                // $this->updateImage($result['product'],$sku_list,$image_list);
                $cnt_success ++;
            } catch (\Exception $e) {
                $redis->rPush($shoplaza_faile_key, $product_id);
                mLog($this->logFileName, $product_id.'========='.$e->getMessage());
                $cnt_faile ++;
                continue;
            }
            sleep(5);

        }

        return ['success' => $cnt_success, 'failed' => $cnt_faile];
    }

}