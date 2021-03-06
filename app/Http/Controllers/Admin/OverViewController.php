<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverViewController extends Controller{

    public function index(Request $request){
        $start = $request->input('start_time');
        $end = $request->input('end_time');
        $accounts = $request->input('accounts');
        if(empty($start) || empty($end) )
            return error(1001,"参数不能空");
        $obj = AdOverview::where('date','>=',$start)->where('date' , '<=' ,$end);
        if(!empty($accounts)){
            if(!is_array($accounts))
                $accounts = [$accounts];
            $obj->whereIn('account_id',$accounts);
        }else{
            $obj->whereNotIn('account_id',[289058739138071,415921722753878]);
        }

        $list =$obj->select('date',DB::raw('SUM(spend) as spend'),DB::raw('SUM(impression) as impression') , DB::raw('SUM(inline_link_clicks) as inline_link_clicks') , DB::raw('SUM(click) as click') , DB::raw('SUM(install) as install') ,
                DB::raw('SUM(landing_page_view) as landing_page_view'),DB::raw('SUM(add_cart) as add_cart'),DB::raw('SUM(purchase) as purchase'),DB::raw('SUM(purchase_value) as purchase_value'))
            ->groupBy('date')
            ->orderBy('date' , 'desc')
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
            $data['inline_link_clicks'] += $value['inline_link_clicks'];
            $list[$key]['ctr'] = $value['impression'] > 0 ? round($value['inline_link_clicks']*100/$value['impression'],2) : 0;
            $list[$key]['cpc'] = $value['inline_link_clicks'] > 0 ? round($value['spend']/$value['inline_link_clicks'],2) : 0;
            $list[$key]['cpm'] = $value['impression'] > 0 ?  round($value['spend']*1000/$value['impression'],2) : 0;
            $list[$key]['cpa'] = $value['purchase'] > 0 ?  round($value['spend']/$value['purchase'],2) : 0;
            $list[$key]['roas'] =$value['purchase_value'] > 0 ? round($value['purchase_value']/$value['spend'],2) : 0;
        }
        $data['ctr'] = $data['impression'] > 0 ? round($data['inline_link_clicks']*100/$data['impression'],2) : 0 ;
        $data['cpc'] = $data['click'] > 0 ?  round($data['spend']/$data['inline_link_clicks'],2) : 0;
        $data['cpm'] = $data['impression'] > 0 ?  round($data['spend']*1000/$data['impression'],2) : 0;
        $data['cpa'] = $data['purchase'] > 0 ?  round($data['spend']/$data['purchase'],2) : 0;
        $data['roas'] =$data['purchase_value'] > 0 ?  round($data['spend']/$data['purchase_value'],2) : 0;
        $accounts = AdAccount::where(['status'=>0])->get();
        return success(['list'=>$list,'total'=>$data,'accounts'=>$accounts]);

    }

    public function getAdAccount(Request $request){
        $user_id = $request->input('user_id');
        $account = AdAccount::where(['user_id'=>$user_id,'status'=>0])->get();
        return success($account);
    }

}
?>