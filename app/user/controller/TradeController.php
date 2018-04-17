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
use think\Validate;
use sms\Msg;
use function Qiniu\json_decode;
/* 交易 */
class TradeController extends UserBaseController
{
    private $m;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('order');
        $this->assign('html_title','交易');
        $this->assign('html_flag','trade');
    }
    /*询价 */
    public function index(){
        $code0=$this->request->param('code0','');
        //查询股票
        if(!empty($code0)){
            $stock=Db::name('stock')->where('code0',$code0)->find();
            $this->assign('stock',$stock);
        }
      
        $guide=Db::name('guide')->where('name','trade')->find();
        $this->assign('html_title','询价');
        
        $this->assign('guide',$guide['title']);
        $this->assign('day',config('day'));
        
        $this->assign('money_off',config('money_off'));
        $this->assign('money_on',config('money_on'));
        return $this->fetch();
    }
    /*  询价 */
    public function ajax_inquiry(){
        
        $user=session('user');
        $data=$this->request->param();
        $stock=Db::name('stock')->where('code0',$data['code0'])->find();
        if(empty($stock)){
            $this->error('股票不存在');
        }
        if($stock['status']!=1){
            $this->error('该股票不能交易');
        }
        $time=time();
        $data_order=[
            'code'=>$stock['code'],
            'code0'=>$stock['code0'],
            'name'=>$stock['name'],
            'uid'=>$user['id'],
            'uname'=>$user['user_nickname'],
            'oid'=>cmf_get_order_sn('yh'),
            'money0'=>$data['money'],
            'month'=>$data['month'],
            'status'=>0,
            'inquiry_time'=>$time,
            'time'=>$time,
        ];
        $m=$this->m;
        $m->insert($data_order);
        $this->success('已提交，等待后台回复',url('buy'));
        
    }
    /*买入界面，即询价后 */
    public function buy(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'),
            'status'=>['in',[0,1,2,3,5]]
        ];
        $list=$m->where($where)->select();
        $this->assign('list',$list);
        $this->assign('order_status',config('order_status'));
        return $this->fetch();
    }
    /*  买入 */
    public function ajax_buy(){
        
        $user=session('user');
        $id=$this->request->param('id',0,'intval');
        $m=$this->m;
        $order=$m->where('id',$id)->find();
        if(empty($order)){
            $this->error('询价信息不存在');
        }
        if($order['status']!=1){
            $this->error('暂时不能买入，请刷新');
        }
        $time=time();
        //要计算行权最后期限,以提前5天提醒用户
        //要重新计算,可以放到后台
        $end_time=$time+$order['month']*30*24*3600;
        $notice_time=$end_time-config('notice_time')*24*3600;
        $data_order=[ 
            'uname'=>$user['user_nickname'], 
            'status'=>0,
            'buy_time'=>$time,
            'time'=>$time,
            'end_time'=>$end_time,
            'notice_time'=>$notice_time,
        ];
        $m->where('id',$id)->update($data_order);
        $this->success('已提交，等待后台回复',url('buy'));
        
    }
    /*买入界面，即询价后 */
    public function store(){
        $m=$this->m;
        //0询价，1询价有结果，2询价失败，3买入，4买入成功，5买入失败，6卖出，7结束
        $where=[
            'uid'=>session('user.id'),
            'status'=>['in',[4,6]]
        ];
        $list=$m->where($where)->select();
        $this->assign('list',$list);
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
        
        $uid=session('user.id');
        $id=$this->request->param('id',0,'intval');
        $m=$this->m;
        $order=$m->where('id',$id)->find();
        if(empty($order)){
            $this->error('无此订单1');
        }
        if($order['uid']!=$uid){
            $this->error('无此订单2');
        }elseif($order['status']!=4){
            $this->error('订单不能行权');
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
        //循环得到各股票的实时数据
        $tmp=[];
        foreach($list as $k=>$v){
            $tmp[]=[
                'name'=>$v['name'],
                'code'=>$v['code'],
                'code0'=>$v['code0'],
                'status'=>$v['status'],
                'price'=>1.22,
                'percent'=>0.2,
            ];
            
        }
       
        $this->assign('list',$tmp);
        
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
        $list=$m->where($where)->select();
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
        $list=$m->where($where)->select();
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
        $this->assign('order',$order);
        $this->assign('html_title','询价详情');
        
        return $this->fetch();
    }
     
}
