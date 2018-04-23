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
    /* 定时执行订单,把未持仓的订单过期掉,把持仓的订单判断是否可行权,每日2点执行 */ 
    public function order_old(){
        //获取凌晨0点时间
        $time=zz_get_time0();
       
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
        //先获取已付款的，
        $where=[
            'status'=>['in',[1,2,3,5]],
            'is_old'=>['eq',0]
        ];
        $m_order->where($where)->update(['is_old'=>1,'time'=>$time]);
        
        //把持仓的订单改为可以行权
        $m_day=Db::name('stock_calendar');
        $tmp=$m_day->where('time',$time)->find();
        if($tmp['type']!=0 || $tmp['is_trade']!=1){
            cmf_log('非交易日，行权日期检查结束','time.log');
            exit('非交易日，结束');
        }
        //获取可行权的天数限制，要买入后超过指定天数才能行权
        $day=config('sell_day');
        
        while($day){
            $time0=$time-86400;
            $tmp=$m_day->where('time',$time0)->find();
            if($tmp['type']!=0 || $tmp['is_trade']!=1){
                $day--;
            }
        }
        
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        //持仓中，且have_time时间大于最后时间的才能行权
        $where=[
            'status'=>['eq',4],
            'is_old'=>['eq',0],
            'have_time'=>['egt',$time0],
           
        ];
        $m_order->where($where)->update(['is_old'=>2,'time'=>$time]);
        cmf_log('行权日期检查结束','time.log');
        exit('行权日期检查结束');
    }
    
    /* 判断订单是否要过期,中午12点执行，发送短信通知 */
    public function sell_old(){
        //获取凌晨0点时间
        $time=zz_get_time0();
        
        $time_day=trim(config('order_notice'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束','time.log');
            exit('重复任务，结束');
        }else{
            cmf_set_dynamic_config(['order_notice'=>date('Y-m-d')]);
        }
        //把持仓的订单改为可以行权
        $m_day=Db::name('stock_calendar');
        //提前多少天提醒
        $day=config('notice_day');
        
        while($day){
            $time0=$time+86400;
            $tmp=$m_day->where('time',$time0)->find();
            if($tmp['type']!=0 || $tmp['is_trade']!=1){
                $day--;
            }
        }
        
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        //持仓中，end_time<=相加后的时间
        $where=[
            'status'=>['eq',4],
            'is_old'=>['eq',2],
            'end_time'=>['elt',$time0],  
        ];
        //批量信息发送
        //$list=$m_order->where($where)->column('id,uid,money0,month,status,name,code0');
        $list=$m_order->where($where)->column('uid');
        $data_msg=[
            'aid'=>1,
            'title'=>'行权期限提醒',
            'content'=>'你的订单即将过期，请注意查看',
            'type'=>2,
            'list'=>$list,
        ];
        zz_msgs($data_msg);
        $m_order->where($where)->update(['is_old'=>3,'time'=>$time]);
        cmf_log('行权日期检查结束','time.log');
        exit('行权日期检查结束');
    }
   
}
