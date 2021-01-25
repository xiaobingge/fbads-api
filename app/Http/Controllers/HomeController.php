<?php

namespace App\Http\Controllers;

use App\Services\ShoplazaService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }


    public function tool(Request $request) {
        $shop_keys = app(ShoplazaService::class)->getToolKeys();
        return view('shopify_tool')->with('shop_keys', $shop_keys);
    }

    public function tool_ajax(Request $request) {
        $type = $request->input('type');
        $shop_key = $request->input('shop_key');
        $text = $request->input('text');
        $async = $request->input('async', 0);
        if (empty($text)) {
            return response()->json(['code' => 2001, 'message' => '参数错误']);
        }

        $shopCheck = app(ShoplazaService::class)->getToolUrl($shop_key);
        if (empty($shopCheck)) {
            return response()->json(['code' => 2001, 'message' => '站点不存在']);
        }

        if ($type == 'url') {
            if ($shopCheck['type'] == 'shoplaza') {
                $html = app(\GuzzleHttp\Client::class)->request('GET', $text)->getBody()->getContents();
                preg_match_all('/<meta\s*property="og:title"\s*content="(.*?)"\s*[\/]?>/', $html, $maths);
                $title = $maths[1][0];
                if (empty($title)) {
                    return response()->json(['code' => 2002, 'message' => 'url 解析错误!C-02']);
                }

                if ($async) {
                    \Artisan::call('check:data', ['shop_type' => $shop_key, '--title' => $title]);
                    return response()->json(['code' => 1000, 'msg' => '脚本执行中，稍后查看']);
                } else {
                    $result = app(ShoplazaService::class)->getToolFixMsg($shop_key, ['title' => $title]);
                    return response()->json($result);
                }

            } else {
                $handle = array_last(explode('/', $text));
                if (empty($handle)) {
                    return response()->json(['code' => 2002, 'message' => 'url 解析错误!C-01']);
                }

                if ($async) {
                    \Artisan::call('check:data', ['shop_type' => $shop_key, '--handle' => $handle]);
                    return response()->json(['code' => 1000, 'msg' => '脚本执行中，稍后查看']);
                } else {
                    $result = app(ShoplazaService::class)->getToolFixMsg($shop_key, ['handle' => $handle]);
                    return response()->json($result);
                }
            }

        } elseif ($type == 'handle') {

            if ($async) {
                \Artisan::call('check:data', ['shop_type' => $shop_key, '--handle' => $text]);
                return response()->json(['code' => 1000, 'msg' => '脚本执行中，稍后查看']);
            } else {
                $result = app(ShoplazaService::class)->getToolFixMsg($shop_key, ['handle' => $text]);
                return response()->json($result);
            }
        } elseif ($type == 'productId') {
            if ($async) {
                \Artisan::call('check:data', ['shop_type' => $shop_key, '--productId' => $text]);
                return response()->json(['code' => 1000, 'msg' => '脚本执行中，稍后查看']);
            } else {
                $result = app(ShoplazaService::class)->getToolFixMsg($shop_key, ['productId' => $text]);
                return response()->json($result);
            }
        } elseif ($type == 'title') {
            if ($async) {
                \Artisan::call('check:data', ['shop_type' => $shop_key, '--title' => $text]);
                return response()->json(['code' => 1000, 'msg' => '脚本执行中，稍后查看']);
            } else {
                $result = app(ShoplazaService::class)->getToolFixMsg($shop_key, ['title' => $text]);
                return response()->json($result);
            }
        }

        return response()->json(['code' => 2001, 'msg' => '参数错误']);
    }



}
