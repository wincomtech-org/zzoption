<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class StockzController extends HomeBaseController
{
    public function _initialize()
    {
        
        parent::_initialize();
    }
    
    /* 查询指数 */
    public function stock_indice(){
         
        $indices=Db::name('stock_indice')->column('id,count,num,percent');
        $this->success('获取成功','',$indices);
    }
    /* 查询股票列表 */
    public function stock_search(){
        $code=$this->request->param('code','','trim');
        $where=['status'=>['eq',1]];
        if($code>0){
            $where['code']=['like','%'.$code.'%'];
        }else{
            $where['name']=['like','%'.$code.'%'];
        }
        $list=Db::name('stock')->where($where)->limit(10)->column('code0,code,name');
        $this->success('获取成功','',$list);
    }
    /* 定时执行订单,把未持仓的订单过期掉 */ 
    public function order_old(){
        //获取凌晨0点时间
        $time=zz_get_time0();
        //24小时过期时间
        $time0=$time-86400;
        $time_day=trim(config('order_old'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束','time.log');
            exit('重复任务，结束');
        }else{
            cmf_set_dynamic_config(['order_old'=>date('Y-m-d')]);
        }
        //所有询价成功没确认买入的否过期
      /*   0 => '询价中',
        1 => '询价成功',
        2 => '询价失败',
        3 => '已付款',
        4 => '持仓中',
        5 => '买入失败',
        6=>'行权中',
        7=>'行权结束',
        8=>'行权过期', */
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        $where=[
            'status'=>['in',[2,5]],
            'is_old'=>['eq',0]
        ];
        $m_order->where($where)->update(['is_old'=>1]);
    }
    /* 定时执行订单,把持仓的订单改为可以行权 */
    public function order_sell(){
        
        //获取凌晨0点时间
        $time=zz_get_time0();
        
        //24小时过期时间
        $time0=$time-86400;
        $time_day=trim(config('order_sell'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束','time.log');
            exit('重复任务，结束');
        }else{
            cmf_set_dynamic_config(['order_sell'=>date('Y-m-d')]);
        }
        $m_day=Db::name('stock_calendar');
        $tmp=$m_day->where('time',$time)->find();
        if($tmp['type']!=0 || $tmp['is_trade']!=1){
            cmf_log('非交易日，结束','time.log');
            exit('非交易日，结束');
        }
        //获取可行权的天数限制，要买入后超过指定天数才能行权
        $day=config('sell_day');
        
        while($day){
            
        }
        //所有询价成功没确认买入的否过期
        /*   0 => '询价中',
         1 => '询价成功',
         2 => '询价失败',
         3 => '已付款',
         4 => '持仓中',
         5 => '买入失败',
         6=>'行权中',
         7=>'行权结束',
         8=>'行权过期', */
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        $where=[
            'status'=>['in',[1,2,3,5]],
            'is_old'=>['eq',0]
        ];
        $m_order->where($where)->update(['is_old'=>1]);
    }
    
}
