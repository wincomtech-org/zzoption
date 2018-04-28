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
use stock\Stock;
class StockzController extends HomeBaseController
{
    private $log;
    public function _initialize()
    {
        
        parent::_initialize();
        $this->log="stockz.log";
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
     
    /* 定时执行订单,把未持仓的订单过期掉,把持仓的订单判断是否可行权,每日2点执行
     * //所有询价成功没确认买入的,过期
     *  //已付款的，要返回权利金,并改为买入失败
     *  //把持仓的订单改为可以行权
     *  */ 
    public function order_old(){
        //获取凌晨0点时间
        $time=zz_get_time0();
        $time1=time();
        $time_day=trim(config('order_old'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束',$this->log);
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
       
        $m_user=Db::name('user');
        //清除密码锁定
        $m_user->where('psw_fail','neq',0)->update(['psw_fail'=>0]);
        $m_order->startTrans();
        //所有询价成功没确认买入的,过期
        $where=[
            'status'=>['in',[1,2,5]],
            'is_old'=>['eq',0]
        ];
        $m_order->where($where)->update(['is_old'=>1,'time'=>$time1]);
        //已付款的，要返回权利金,并改为买入失败
        $where=[
            'o.status'=>['eq',3], 
            'o.is_old'=>['eq',0]
        ];
        $list=$m_order 
        ->alias('o')
        ->join('cmf_user u','u.id=o.uid')
        ->where($where)
        ->column('o.id,o.uid,o.name,o.code0,o.money0,o.money1,o.month,u.mobile,u.user_nickname as uname,u.money as umoney');
        //返回权利金的改为买入失败
        $where=[
            'status'=>['eq',3],
            'is_old'=>['eq',0]
        ];
        $m_order->where($where)->update(['is_old'=>1,'time'=>$time1,'status'=>5]);
       
        //记录资金明细
        $data_money=[];
        foreach ($list as $k=>$v){
            
            $data_money[]=[
                'uid'=>$v['uid'],
                'money'=>$v['money1'],
                'status'=>1,
                'type'=>2,
                'time'=>$time1,
                'insert_time'=>$time1,
                'dsc'=>zz_msg_dsc($v).'买入失败，退款',
            ];
            $money=bcadd($v['umoney'],$v['money1'],2);
            $m_user->where('id',$v['uid'])->update(['money'=>$money]);
        }
        Db::name('money')->insertAll($data_money);
        //系统消息
        $data_msg=[
            'aid'=>1,
            'data'=>$list,
            'title'=>'买入失败，退款',
            'type'=>2, 
        ];
        
        zz_msgs($data_msg);
       
       
        $m_order->commit();
        
        //把持仓的订单改为可以行权
        $m_day=Db::name('stock_calendar');
        $tmp=$m_day->where('time',$time)->find();
        if($tmp['is_trade']!=1){
            cmf_log('非交易日，行权日期检查结束',$this->log);
            exit('非交易日，结束');
        }
        //获取可行权的天数限制，要买入后超过指定天数才能行权
        $day=config('sell_day');
        $time0=$time;
       
        while($day){ 
            $tmp=$m_day->where('time',$time0)->find();
            $time0=$time0-86400;
            if($tmp['is_trade']==1){
                $day--;
            }
        }
        
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
       
        //持仓中，且buy_time时间大于最后时间的才能行权
        
        $where=[
            'status'=>['eq',4],
            'is_old'=>['eq',0],
            'buy_time'=>['egt',$time0], 
        ];
        $m_order->where($where)->update(['is_old'=>2,'time'=>$time1]);
      
        cmf_log('可行权日期检查结束',$this->log);
        exit('行权日期检查结束');
    }
    
    /* 判断订单是否要过期,下午2点执行，发送短信通知 */
    public function sell_notice(){
        //获取凌晨0点时间
        $time=zz_get_time0();
        $time1=time();
        $m_day=Db::name('stock_calendar');
        $tmp=$m_day->where('time',$time)->find();
        if($tmp['is_trade']!=1){
            cmf_log('非交易日，行权即将过期检查结束',$this->log);
            exit('非交易日，结束');
        }
        //不需要判断重复，重复无用
       /*  $time_day=trim(config('sell_notice'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束',$this->log);
            exit('重复任务，结束');
        }else{
            cmf_set_dynamic_config(['sell_notice'=>date('Y-m-d')]);
        } */
       
        //提前多少天提醒,就加多少天得到未来的结束时间
        $day=config('notice_day');
        $time0=$time;
        
        while($day){
            $tmp=$m_day->where('time',$time0)->find();
            $time0=$time0+86400;
            if($tmp['is_trade']==1){
                $day--; 
            }
        }
        
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        //持仓中，end_time<=相加后的时间
        $m_order->startTrans();
        //批量信息发送
        $where=[ 
            'o.status'=>['eq',4],
            'o.is_old'=>['eq',2],
            'o.end_time'=>['elt',$time0],
        ];
        $list=$m_order
        ->alias('o')
        ->join('cmf_user u','u.id=o.uid')
        ->where($where)
        ->column('o.id,o.uid,o.name,o.code0,o.money0,o.money1,o.month,u.mobile,u.user_nickname as uname');
        //系统消息
        $data_msg=[
            'aid'=>1,
            'data'=>$list,
            'title'=>'行权期限即将到期',
            'type'=>2,
        ];
        
        zz_msgs($data_msg);
         
        $where=[
            'status'=>['eq',4],
            'is_old'=>['eq',2],
            'end_time'=>['elt',$time0],
        ];
        $m_order->where($where)->update(['is_old'=>3,'time'=>$time1,'notice_time'=>$time1]);
        $m_order->commit();
        cmf_log('行权日期检查结束',$this->log);
        exit('行权日期检查结束');
    }
    /* 判断订单是否今日过期,下午2点30执行，发送短信通知 */
    public function sell_old(){
        //获取凌晨0点时间
        $time=zz_get_time0();
        $time1=time();
        $m_day=Db::name('stock_calendar');
        $tmp=$m_day->where('time',$time)->find();
        if($tmp['is_trade']!=1){
            cmf_log('非交易日，行权今日过期检查结束',$this->log);
            exit('非交易日，结束');
        }
        // 判断重复 
         $time_day=trim(config('sell_old'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束',$this->log);
            exit('重复任务，结束');
        }else{
            cmf_set_dynamic_config(['sell_old'=>date('Y-m-d')]);
        } 
        
        //加一个工作日，防止结束时间在假期
        $day=1;
        $time0=$time;
        
        while($day){
            $tmp=$m_day->where('time',$time0)->find();
            $time0=$time0+86400;
            if($tmp['is_trade']==1){
                $day--; 
            }
        }
        
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        //持仓中，end_time<=相加后的时间
        $m_order->startTrans();
        //批量信息发送
        $where=[
            'o.status'=>['eq',4],
            'o.is_old'=>['eq',3],
            'o.end_time'=>['elt',$time0],
        ];
        $list=$m_order
        ->alias('o')
        ->join('cmf_user u','u.id=o.uid')
        ->where($where)
        ->column('o.id,o.uid,o.name,o.code0,o.money0,o.money1,o.month,u.mobile,u.user_nickname as uname');
        //系统消息
        $data_msg=[
            'aid'=>1,
            'data'=>$list,
            'title'=>'行权期限今日到期!',
            'type'=>2,
        ];
        
        zz_msgs($data_msg);
        
        $where=[
            'status'=>['eq',4],
            'is_old'=>['eq',3],
            'end_time'=>['elt',$time0],
        ];
        $m_order->where($where)->update(['is_old'=>4,'time'=>$time1]);
        
        $m_order->commit();
        cmf_log('行权日期今日到期检查结束',$this->log);
        exit('行权日期检查结束');
    }
    /* 订单今日过期,自动行权,下午2点50执行，发送短信通知 */
    public function sell_auto(){
        //获取凌晨0点时间
        $time=zz_get_time0();
        $time1=time();
        $m_day=Db::name('stock_calendar');
        $tmp=$m_day->where('time',$time)->find();
        if($tmp['is_trade']!=1){
            cmf_log('非交易日，自动行权检查结束',$this->log);
            exit('非交易日，结束');
        }
        //不需要判断重复，重复无用
          $time_day=trim(config('sell_auto'));
        //判断重复任务
        if(strtotime($time_day)===$time){
            cmf_log('重复任务，结束',$this->log);
            exit('重复任务，结束');
        }else{
            cmf_set_dynamic_config(['sell_auto'=>date('Y-m-d')]);
        }
         
         
        //      is_old是否过期，0正常，1过期,2可以行权，3即将过期
        $m_order=Db::name('order');
        $m_order->startTrans();
        
        //批量信息发送
        $where=[
            'o.status'=>['eq',4],
            'o.is_old'=>['eq',4], 
        ];
        $list=$m_order
        ->alias('o')
        ->join('cmf_user u','u.id=o.uid')
        ->where($where)
        ->column('o.id,o.uid,o.name,o.code0,o.money0,o.money1,o.month,u.mobile,u.user_nickname as uname');
        //系统消息
        $data_msg=[
            'aid'=>1,
            'data'=>$list,
            'title'=>'行权期限已到期，系统自动行权',
            'type'=>2,
        ];
        
        zz_msgs($data_msg);
        
        $where=[
            'status'=>['eq',4],
            'is_old'=>['eq',4], 
        ];
        $m_order->where($where)->update(['status'=>6,'time'=>$time1]);
        $m_order->commit();
        cmf_log('行权日期今日到期检查结束',$this->log);
        exit('行权日期检查结束');
    }
    
     
   
}
