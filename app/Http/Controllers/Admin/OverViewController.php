<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdOverView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverViewController extends Controller{


    public function index(Request $request){
        $start = $request->input('start_time');
        $end = $request->input('end_time');
        $accounts = $request->input('accounts');
        if(empty($start) || empty($end) )
            return error(1001,"参数不能空");
        $obj = AdOverView::where('date','>=',$start)->where('date' , '<=' ,$end);
        if(!empty($accounts))
            $obj->whereIn('account_id',$accounts);
        $list =$obj->select('date',DB::raw('SUM(spend) as spend'),DB::raw('SUM(impression) as impression') , DB::raw('SUM(click) as click') , DB::raw('SUM(install) as install') ,
                DB::raw('SUM(landing_page_view) as landing_page_view'),DB::raw('SUM(add_cart) as add_cart'),DB::raw('SUM(purchase) as purchase'),DB::raw('SUM(purchase_value) as purchase_value'))
            ->group('date')
            ->order('date desc')
            ->get();
        $data = [];
        foreach($list as $key=>$value){
            $data['spend'] += $value['spend'];
            $data['impression'] += $value['impression'];
            $data['click'] += $value['click'];
            $data['install'] += $value['install'];
            $data['landing_page_view'] +=$value['landing_page_view'];
            $data['add_cart'] +=$value['add_cart'];
            $data['purchase'] += $value['purchase'];
            $data['purchase_value'] += $value['purchase_value'];
            $list[$key]['ctr'] = round($value['click']*100/$value['impression'],2);
            $list[$key]['cpc'] = round($value['spend']/$value['click'],2);
            $list[$key]['cpm'] = round($value['spend']*1000/$value['impression'],2);
            $list[$key]['cpa'] = round($value['spend']/$value['purchase'],2);
            $list[$key]['roas'] = round($value['spend']/$value['purchase_value'],2);
        }
        $data['ctr'] = round($data['click']*100/$data['impression'],2);
        $data['cpc'] = round($data['spend']/$data['click'],2);
        $data['cpm'] = round($data['spend']*1000/$data['impression'],2);
        $data['cpa'] = round($data['spend']/$data['purchase'],2);
        $data['roas'] = round($data['spend']/$data['purchase_value'],2);
        return success(['list'=>$list,'total'=>$data]);

    }

    public function getAdAccount(Request $request){
        $user_id = $request->input('user_id');
        $account = AdAccount::where(['user_id'=>$user_id])->get();
        return success($account);
    }

}
?>