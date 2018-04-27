<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\UserBaseController;
use think\Db;

use stock\Stock;
/* 交易 */
class TradeController extends UserBaseController
{
    private $m;
    private $sort;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('order');
        $this->sort='time desc';
        $this->assign('html_title','交易');
        $this->assign('html_flag','trade');
    }
    
    /*买入界面，即询价后 */
    public function buy(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'),
            'status'=>['in',[0,1,2,3,5]],
            'is_old'=>0,
        ];
        $list=$m->where($where)->order($this->sort)->select();
        $this->assign('list',$list);
        $this->assign('html_title','买入');
        $this->assign('order_status',config('order_status'));
        return $this->fetch();
    }
    /*  买入 */
    public function ajax_buy(){
        $this->time_check();
        $uid=session('user.id');
        
        $id=$this->request->param('id',0,'intval');
        $m=$this->m;
        $order=$m->where('id',$id)->find();
        if(empty($order)){
            $this->error('询价信息不存在');
        }
        if($order['status']!=1){
            $this->error('数据错误，请刷新');
        }
        if($order['is_old']!=0){
            $this->error('数据错误，请刷新');
        }
        if($uid!=$order['uid']){
            session('user',null);
            $this->error('这不是你的信息','/');
        }
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        if($user['is_name']==0){
            $this->error('交易前请先实名认证',url('user/info/info'));
        }
        if($user['money']<$order['money1']){
            $this->error('余额不足，请先充值',url('portal/index/my'));
        }
        $money_new=bcsub($user['money'],$order['money1'],2);
        $time=time();
         
        $data_order=[ 
            'uname'=>$user['user_nickname'], 
            'status'=>3,
            'buy_time'=>$time,
            'time'=>$time, 
            'end_time'=>strtotime('+'.$order['month'].' months'), 
        ];
        $m->startTrans();
        $m->where('id',$id)->update($data_order);
        $m_user->where('id',$uid)->update(['money'=>$money_new]);
        $dsc=zz_msg_dsc($order);
         //记录资金明细
        $data_money=[
            'uid'=>$uid,
            'money'=>'-'.$order['money1'],
            'status'=>1,
            'type'=>1,
            'time'=>$time,
            'insert_time'=>$time,
            'dsc'=>$dsc.'买入', 
        ];
        Db::name('money')->insert($data_money);
        $m->commit();
        $this->success('已提交，等待后台处理',url('buy'));
        
    }
    /*买入界面，即询价后 */
    public function store(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'),
            'status'=>['in',[4,6]]
        ];
        $list=$m->where($where)->order($this->sort)->select();
        $this->assign('list',$list);
        $this->assign('html_title','持仓');
        $this->assign('order_status',config('order_status'));
        return $this->fetch();
    }
    /*持仓 订单 */
    public function order(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6行权中，7行权结束,8过期
        $id=$this->request->param('id',0,'intval');
         $uid=session('user.id');
        $order=$m->where('id',$id)->find();
        if(empty($order)){
            $this->error('无此订单1');
        }
        if($order['uid']!=$uid){
            $this->error('无此订单2');
        }elseif($order['status']>6 ){
            $this->redirect(url('order_old',['id'=>$id]));
        }elseif($order['status']!=4 && $order['status']!=6){
            $this->error('无此订单3');
        }
        $stock=new Stock();
        $price=$stock->getPrice('s_'.$order['code0']);
        
        $price=$price['s_'.$order['code0']];
        $order['price2']=$price['price'];
        $order['money2']=zz_get_money($order['price1'], $order['price2'], $order['money0']);
        //要查询当前价格，计算浮盈
        $this->assign('order',$order);
        
        return $this->fetch();
    }
    /*平仓 订单 */
    public function order_old(){
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $uid=session('user.id');
        $order=$m->where('id',$id)->find();
        if(empty($order)){
            $this->error('无此订单1');
        }
        if($order['uid']!=$uid){
            $this->error('无此订单2');
        }elseif($order['status']<7){
            $this->error('无此订单3');
        } 
         
        $this->assign('order',$order);
        
        return $this->fetch();
    }
    /*  行权 */
    public function ajax_sell(){
        $this->time_check();
        $uid=session('user.id');
        $id=$this->request->param('id',0,'intval');
        $m=$this->m;
        $order=$m->where('id',$id)->find();
        if(empty($order)){
            $this->error('无此订单1');
        }
        if($order['uid']!=$uid){
            $this->error('无此订单2');
        }elseif($order['status']!=4 || $order['is_old']<2){
            //is_old是否过期，0正常，1过期,2可以行权，3即将过期
            $this->error('订单不能行权');
        }
        if($uid!=$order['uid']){
            session('user',null);
            $this->error('这不是你的信息','/');
        }
        $time=time();
         
        $data_order=[ 
            'status'=>6,
             'sell_time'=>$time,
            'time'=>$time
        ];
        $m->where('id',$id)->update($data_order);
        $this->success('已提交，等待后台回复');
        
    }
    /*自选股票 */
    public function collect(){
        $m=Db::name('collect');
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'),
            
        ];
        $list=Db::name('collect')
        ->field('stock.*')
        ->alias('collect')
        ->join('cmf_stock stock','stock.id=collect.stock')
        ->where('collect.uid',session('user.id'))
        ->order('collect.id asc')
        ->select();
        $stock=new Stock();
        //循环得到各股票的实时数据
        $codes='';
        foreach($list as $k=>$v){
            $codes.=',s_'.$v['code0']; 
        }
        $codes=substr($codes, 1);
        $prices=$stock->getPrice($codes);
     
        foreach($list as $k=>$v){
            $price=$prices['s_'.$v['code0']];
            $tmp[]=[
                'name'=>$v['name'],
                'code'=>$v['code'],
                'code0'=>$v['code0'],
                'status'=>$v['status'],
                'price'=>$price['price'],
                'percent'=>$price['percent'],
            ];
        }
       
        $this->assign('list',$tmp);
        $this->assign('html_title','自选');
        return $this->fetch();
    }
    /*查询 */
    public function query(){
        
        $this->assign('html_title','查询');
         
        return $this->fetch();
    }
    /*询价记录*/
    public function inquiry_record(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'), 
        ];
        $list=$m->where($where)->order($this->sort)->select();
        $this->assign('list',$list);
        $this->assign('html_title','询价记录');
        
        return $this->fetch();
    }
    /*平仓记录 */
    public function flat_record(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'),
            'status'=>['in',[7,8]],
        ];
        $list=$m->where($where)->order($this->sort)->select();
        $this->assign('list',$list);
        $this->assign('html_title','平仓历史');
        
        return $this->fetch();
    }
    /*询价xaignq*/
    public function inquiry_details(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'id'=>$this->request->param('id',0,'intval'),
            'uid'=>session('user.id'), 
        ];
        $order=$m->where($where)->find();
         $status=config('order_status');
         $order['status_name']=$status[$order['status']];
         $stock=new Stock();
         $price=$stock->getPrice('s_'.$order['code0']);
         
         $price=$price['s_'.$order['code0']];
         $order['price2']=$price['price'];
         $order['money2']=zz_get_money($order['price1'], $order['price2'], $order['money0']);
        $this->assign('order',$order);
        $this->assign('html_title','询价详情');
        
        return $this->fetch();
    }
    /* 判断是否交易时间 */
    public function time_check(){
       
        $time=time();
        $day=strtotime(date('Y-m-d',$time));
        $tmp=$time-$day;
        if($tmp<600 || $tmp>86390){
            $this->error('非交易时间');
        }
    }
     
     
}
