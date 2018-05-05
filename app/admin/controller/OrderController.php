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
        $this->assign('is_old',config('is_old'));
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
        $shop=session('shop');
        if($shop['id']==1){
            $where=[];
        }elseif($shop['type']==2){
            $where=['o.shop'=>['eq',$shop['id']]]; 
        }else{
            $ids=Db::name('shop')->where('fid',$shop['id'])->column('id');
            $ids[]=$shop['id'];
            $where=['o.shop'=>['in',$ids]];
        }
        
        $data=$this->request->param();
        if(isset($data['status']) &&  $data['status']!='-1'){
            $where['o.status']=['eq',$data['status']];
        }else{
            $data['status']='-1';
        }
        
        if(empty($data['uname'])){
            $data['uname']='';
        }else{
            $where['o.uname']=['eq',$data['uname']];
        }
        if(empty($data['code'])){
            $data['code']='';
        }else{
            $where['o.code']=['eq',$data['code']];
        }
        $list= $m
        ->field('o.*,s.name as sname,s.code as scode')
        ->alias('o')
        ->join('cmf_shop s','s.id=o.shop')
        ->where($where)
        ->order($this->order)
        ->paginate(10);
       
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
        //当前价格和浮盈
       
        $stock=new \stock\Stock();
        $prices=$stock->getPrice('s_'.$info['code0']);
        $price=empty($prices['s_'.$info['code0']])?null:$prices['s_'.$info['code0']];
        $info['price2_tmp']=$price['price'];
        $edit='edit_inquiry';
        if($info['status']<3){
            $edit='edit_inquiry';
        }elseif($info['status']<6){
            $edit='edit_buy';
        }else{
            $edit='edit_sell';
            if($info['status']==6 && !empty($info['price2_tmp'])){
                $info['money2_tmp']=zz_get_money($info['price1_0'], $info['price2_tmp'], $info['money0']);
            }
        }
       
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
        $this->time_check();
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
        //如果只是修改备注 
        if($data_order['status']==$info['status'] && ($data_order['status']!=1 || $data_order['money1']==$info['money1'])){
            
            $data_action['action'].=',仅保存信息';
            $m->where('id',$data['id'])->update($data_order);
            Db::name('action')->insert($data_action);
            $this->success('已保存信息',url('index'));
        }
        
        Db::startTrans();
       
        $m->where('id',$data['id'])->update($data_order);
        //询价结果变化通知客户
        $dsc=zz_msg_dsc($info);
       
        if($info['status']==1){
            if($data_order['money1']<=100 || $data_order['price1_0']<=0){
                $this->error('请填写权利金和期初价格');
            }
            $dsc.='询价成功，权利金为'.$data_order['money1'];
        }else{
            $dsc.='询价失败';
        }
        $user=Db::name('user')->where('id',$info['uid'])->find();
        //先保存消息内容再保存用户消息连接
        $data_msg=[
            'aid'=>$data_action['aid'],
            'title'=>'询价结果通知',
            'content'=>$dsc,
            'type'=>2,
            'uid'=>$user['id'],
            'mobile'=>$user['mobile'],
            'uname'=>$user['user_nickname'],
            'sms'=>'order',
        ];
        zz_msg($data_msg); 
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
        $this->time_check();
       
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
        //如果只是修改备注
        if($data_order['status']==$info['status']){
            $data_action['action'].=',仅保存备注';
            $m->where('id',$data['id'])->update($data_order);
            Db::name('action')->insert($data_action);
            $this->success('已保存备注信息',url('index'));
        }
        
        
        Db::startTrans();
        
        $m->where('id',$data['id'])->update($data_order);
        //若选择买入失败则用户付款会返还用户余额，若由失败改为持仓中会从余额中扣款，余额不足则失败
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['uid'])->find();
        //询价结果变化通知客户
       
        $dsc=zz_msg_dsc($info);
        if($data_order['status']==4){
            $data_order['have_time']=$data_order['time'];
            $dsc.='买入成功';
        }else{
            $dsc.='买入失败';
        }
        
        if($info['status']<5 && $data_order['status']==5){
            //退款
            $money_tmp=$info['money1'];
            $dsc.=',退还费用';
        }elseif($info['status']==5 && $data_order['status']<5){
            //退款改为买入，需要付款 
            $money_tmp='-'.$info['money1']; 
            $dsc.=',支付费用';
        }
        //是否有资金操作
        if(isset($money_tmp)){ 
            $money=bcsub($user['money'],$info['money1'],2);
            if($money<0){
                $this->error('用户余额不足');
            }
            $m_user->where('id',$info['uid'])->update(['money'=>$money]);
            $dsc.=$info['money1'].'元'; 
            //记录资金明细
            $data_money=[
                'uid'=>$user['id'],
                'money'=>$money_tmp,
                'status'=>1,
                'type'=>1,
                'time'=>$data_order['time'],
                'insert_time'=>$data_order['time'],
                'dsc'=>$dsc,
            ];
            Db::name('money')->insert($data_money);
        }
        //先保存消息内容再保存用户消息连接
        $data_msg=[
            'aid'=>$data_action['aid'],
            'title'=>'买入结果通知',
            'content'=>$dsc,
            'type'=>2,
            'uid'=>$user['id'],
            'mobile'=>$user['mobile'],
            'uname'=>$user['user_nickname'],
            'sms'=>'order',
        ];
       
        zz_msg($data_msg);
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
        $this->time_check();
       
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
            'price2_0'=>round($data['price2_0'],4),
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
        //如果只是修改备注
        if($data_order['price2_0']==$info['price2_0'] && $data_order['status']==$info['status']){
            $data_action['action'].='，仅保存信息';
            $m->where('id',$data['id'])->update($data_order);
            Db::name('action')->insert($data_action);
            $this->success('已保存备注信息',url('index'));
        }
        Db::startTrans();
        
      
        //若选择行权成功则盈利返还用户余额，若由行权成功、过期改为行权中会从余额中扣款，余额不足则失败
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['uid'])->find();
        $dsc=zz_msg_dsc($info);
        
        if($data_order['status']==7){
            if($data_order['price2_0']<=0){
                $this->error('请填写期末价格');
            }
            $data_order['money2']=zz_get_money($info['price1_0'], $data_order['price2_0'], $info['money0']);
            //之前结算过，计算新旧盈利的差值后给用户 
            if($info['status']==7){
                $money_add=bcsub($data_order['money2'],$info['money2'],2);
                $dsc.='管理员修改行权结果,重新计算盈利'.$money_add.'元';
            }else{
                $money_add=$data_order['money2'];
                $dsc.='行权成功，计算盈利'.$money_add.'元';
            }
            $data_order['out_time']=$data_order['time'];
            //用户余额操作和用户资金明细记录
            $money=bcadd($user['money'],$money_add,2); 
           
        }else{
            //非行权结束则期末价格归0,从用户余额中扣除盈利
            $data_order['price2_0']=0;
            $data_order['price2']=0;
            $data_order['money2']=0;
            //之前行权过的从用户余额中扣除盈利
            if($info['status']==7){
                $money_add=bcsub($data_order['money2'],$info['money2'],2);
                $dsc.='管理员修改行权结果,扣除原盈利'.$money_add.'元';
                $money=bcsub($user['money'],$info['money2'],2); 
            } 
             
        }  
      
        $m->where('id',$data['id'])->update($data_order);
       
        //记录资金明细
        if(isset($money_add)){ 
             
            $m_user->where('id',$info['uid'])->update(['money'=>$money]);
            $data_money=[
                'uid'=>$user['id'],
                'status'=>1,
                'type'=>1,
                'time'=>$data_order['time'],
                'insert_time'=>$data_order['time'],
                'dsc'=>$dsc,
                'money'=>$money_add,
            ]; 
            Db::name('money')->insert($data_money);
            $data_msg=[
                'aid'=>$data_action['aid'],
                'title'=>'行权结果通知',
                'content'=>$dsc,
                'type'=>2,
                'uid'=>$user['id'],
                'mobile'=>$user['mobile'],
                'uname'=>$user['user_nickname'],
                'sms'=>'order',
            ];
            
            zz_msg($data_msg);
        }
        Db::name('action')->insert($data_action);
        Db::commit();
        
        $this->success('保存成功！',url('index'));
        
    }
    /**
     * 管理员代行权
     * @adminMenu(
     *     'name'   => '管理员代行权',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '管理员代行权',
     *     'param'  => ''
     * )
     */
    public function admin_sell()
    {
        $this->time_check();
        
        $m=$this->m;
        
        $data=$this->request->param();
        $where=['id'=>$data['id']];
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('订单不存在');
        }
        if($info['status']!=4 || $info['is_old']!=0){
            $this->error('信息错误');
        }
        $time=time();
        $data_order=[
            'status'=>6,  
            'time'=>$time,
            'dsc'=>(empty($info['dsc'])?'':($info['dsc'].'，')).'管理员代行权',
            'sell_time'=>$time,
            'out_time'=>$time,
        ];
         
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'key'=>$info['oid'],
            'time'=>$data_order['time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'代行权',
            
        ];
         
        Db::startTrans();
         
        //管理员代行权，通知用户
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['uid'])->find();
        $dsc=zz_msg_dsc($info);
         
        $m->where('id',$data['id'])->update($data_order);
        
        $data_msg=[
            'aid'=>$data_action['aid'],
            'title'=>'管理员代行权',
            'content'=>$dsc.'管理员代行权',
            'type'=>2,
            'uid'=>$user['id'],
            'mobile'=>$user['mobile'],
            'uname'=>$user['user_nickname'],
            'sms'=>'order',
        ];
        
        zz_msg($data_msg);
        
        Db::name('action')->insert($data_action);
        Db::commit();
        
        $this->success('保存成功！',url('edit',['id'=>$info['id']]));
        
    }
    /**
     * 管理员代买入
     * @adminMenu(
     *     'name'   => '管理员代买入',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '管理员代买入',
     *     'param'  => ''
     * )
     */
    public function admin_buy()
    {
        $this->time_check();
        
        $m=$this->m;
        
        $data=$this->request->param();
        $where=['id'=>$data['id']];
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('订单不存在');
        }
        if($info['status']!=1 || $info['is_old']!=0){
            $this->error('信息错误');
        }
        //余额不足则失败
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['uid'])->find();
        $money=bcsub($user['money'],$info['money1'],2);
        if($money<0){
            $this->error('用户余额不足');
        }
        $time=time();
        $data_order=[
            'status'=>4,
            'time'=>$time,
            'dsc'=>(empty($info['dsc'])?'':($info['dsc'].'，')).'管理员代买入',
            'buy_time'=>$time,
            'have_time'=>$time,
            'end_time'=>strtotime('+'.$info['month'].' months'), 
        ];
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'key'=>$info['oid'],
            'time'=>$data_order['time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'代买入',
            
        ];
        
        Db::startTrans();
         
        $dsc=zz_msg_dsc($info);
        
        $m->where('id',$data['id'])->update($data_order);
        
        $m_user->where('id',$info['uid'])->update(['money'=>$money]);
        $dsc.='管理员代买入,花费'.$info['money1'].'元';
        //记录资金明细
        $data_money=[
            'uid'=>$user['id'],
            'money'=>'-'.$info['money1'],
            'status'=>1,
            'type'=>1,
            'time'=>$data_order['time'],
            'insert_time'=>$data_order['time'],
            'dsc'=>$dsc,
        ];
        Db::name('money')->insert($data_money);
         
        $data_msg=[
            'aid'=>$data_action['aid'],
            'title'=>'管理员代买入',
            'content'=>$dsc,
            'type'=>2,
            'uid'=>$user['id'],
            'mobile'=>$user['mobile'],
            'uname'=>$user['user_nickname'],
            'sms'=>'order',
        ]; 
        
        zz_msg($data_msg);
       
        Db::name('action')->insert($data_action);
        Db::commit();
        
        $this->success('保存成功！',url('edit',['id'=>$info['id']]));
        
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
