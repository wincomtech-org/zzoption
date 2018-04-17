<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
/**
 * Class orderController
 * @package app\admin\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'订单管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'',
 *     'remark' =>'订单管理'
 * )
 *
 */
class OrderController extends AdminBaseController
{
    private $m;
    private $order;
    private $order_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('order');
        $this->order='time desc';
        $this->order_status=config('order_status');
        $this->assign('flag','订单');
        
        $this->assign('order_status', $this->order_status);
    }
     
    /**
     * 订单列表
     * @adminMenu(
     *     'name'   => '订单列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(isset($data['status']) &&  $data['status']!='-1'){
            $where['status']=$data['status'];
        }else{
            $data['status']='-1';
        }
        
        if(empty($data['uname'])){
            $data['uname']='';
        }else{
            $where['uname']=$data['uname'];
        }
        if(empty($data['code'])){
            $data['code']='';
        }else{
            $where['code']=$data['code'];
        }
        $list= $m->where($where)->order($this->order)->paginate(10);
       
        // 获取分页显示
        $page = $list->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 订单查看
     * @adminMenu(
     *     'name'   => '订单查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此订单不存在');
        }
        $edit='edit_inquiry';
        if($info['status']<3){
            $edit='edit_inquiry';
        }elseif($info['status']<6){
            $edit='edit_buy';
        }else{
            $edit='edit_sell';
        }
        //当前价格和浮盈
        $info['price2_tmp']=0;
        $info['money2_tmp']=0;
        $this->assign('info',$info); 
        return $this->fetch($edit);
    }
    
    /**
     * 订单询价执行
     * @adminMenu(
     *     'name'   => '订单询价执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单询价执行',
     *     'param'  => ''
     * )
     */
    public function inquiry_do()
    {
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $m=$this->m;
        
        $data=$this->request->param();
        $where=['id'=>$data['id']];
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('订单不存在');
        }
        if($info['status']>2 || $info['is_old']==1){
            $this->error('不能修改订单询价信息');
        }
        $data_order=[
            'status'=>$data['status'],
            'money1'=>round($data['money1'],2),
            'price1'=>round($data['price1_0'],2),
            'price1_0'=>$data['price1_0'],
            'time'=>time(),
            'dsc'=>$data['dsc'],
        ];
        $statuss=$this->order_status;
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'key'=>$info['oid'],
            'time'=>$data_order['time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'询价处理结果为'.$statuss[$data['status']],
            
        ]; 
        Db::startTrans();
       
        $m->where('id',$data['id'])->update($data_order);
        Db::name('action')->insert($data_action);
        Db::commit();
        
        
        
        $this->success('保存成功！',url('index'));
        
    }
    /**
     * 订单买入执行
     * @adminMenu(
     *     'name'   => '订单买入执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单买入执行',
     *     'param'  => ''
     * )
     */
    public function buy_do()
    {
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $m=$this->m;
        
        $data=$this->request->param();
        $where=['id'=>$data['id']];
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('订单不存在');
        }
        if($info['status']<3 || $info['status']>5 || $info['is_old']==1){
            $this->error('不能修改订单买入信息');
        }
        $data_order=[
            'status'=>$data['status'], 
            'time'=>time(),
            'dsc'=>$data['dsc'],
        ];
        $statuss=$this->order_status;
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'key'=>$info['oid'],
            'time'=>$data_order['time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'买入处理结果为'.$statuss[$data['status']],
            
        ];
        
        
        
        Db::startTrans();
        
        $m->where('id',$data['id'])->update($data_order);
        //若选择买入失败则用户付款会返还用户余额，若由失败改为持仓中会从余额中扣款，余额不足则失败
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['uid'])->find();
        if($info['status']<5 && $data_order['status']==5){
            //退款
            $money=bcadd($user['money'],$info['money1'],2);
            $m_user->where('id',$info['uid'])->update(['money'=>$money]);
        }elseif($info['status']==5 && $data_order['status']<5){
            //退款改为买入，需要付款
            $money=bcsub($user['money'],$info['money1'],2);
            if($money<0){
                $this->error('用户余额不足');
            }
            $m_user->where('id',$info['uid'])->update(['money'=>$money]);
        }
        
        Db::name('action')->insert($data_action);
        Db::commit();
         
        $this->success('保存成功！',url('index'));
        
    }
    /**
     * 订单行权执行
     * @adminMenu(
     *     'name'   => '订单行权执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单行权执行',
     *     'param'  => ''
     * )
     */
    public function sell_do()
    {
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $m=$this->m;
        
        $data=$this->request->param();
        $where=['id'=>$data['id']];
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('订单不存在');
        }
        if($info['status']<6 || $info['status']>7 || $info['is_old']==1){
            $this->error('不能修改订单行权信息');
        }
        if(!in_array($data['status'], [4,6,7])){
            $this->error('信息错误');
        }
        $data_order=[
            'status'=>$data['status'],
            'price2_0'=>$data['price2_0'],
            'price2'=>round($data['price2_0'],2),
            'time'=>time(),
            'dsc'=>$data['dsc'],
        ];
       
        $statuss=$this->order_status;
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'key'=>$info['oid'],
            'time'=>$data_order['time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'行权处理结果为'.$statuss[$data['status']],
            
        ]; 
        
        Db::startTrans();
        
      
        //若选择行权成功则盈利返还用户余额，若由行权成功、过期改为行权中会从余额中扣款，余额不足则失败
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['uid'])->find();
    
        if($data_order['status']==7){
            $data_order['money2']=zz_get_money($info['price1_0'], $data_order['price2_0'], $info['money0']);
          //不管之前是否结算过，计算新旧盈利的差值后给用户 
            $money_add=bcsub($data_order['money2'],$info['money2'],2);
            //点击行权结束就重新计算收益
            $money=bcadd($user['money'],$money_add,2); 
        }else{
            //非行权结束则期末价格归0,从用户余额中扣除盈利
            $data_order['price2_0']=0;
            $data_order['price2']=0;
            $data_order['money2']=0;
            $money=bcsub($user['money'],$info['money2'],2); 
        } 
        if($money<0){
            $this->error('用户余额不足');
        } 
        $m_user->where('id',$info['uid'])->update(['money'=>$money]);
        $m->where('id',$data['id'])->update($data_order);
        Db::name('action')->insert($data_action);
        Db::commit();
        
        $this->success('保存成功！',url('index'));
        
    }
    
}
