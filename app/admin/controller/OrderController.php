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
        $this->assign('info',$info); 
        return $this->fetch($edit);
    }
    /**
     * 订单编辑执行
     * @adminMenu(
     *     'name'   => '订单编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单编辑执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
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
            $this->error('订单不存在，请刷新页面');
        }
        if($info['status']>0){
            $this->error('不能修改订单信息');
        }
        if($data['status']!=1 && $data['status']!=4){
            $this->error('信息错误');
        }
        $statuss=$this->order_status;
        $data['update_time']=time();
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'time'=>$data['update_time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'处理结果为'.$statuss[$data['status']],
            
        ];
       
        
        
        //订单成功增加订单信息，订单失败要返回用户余额
        $m_user=Db::name('user');
        $user=$m_user->where('id',$info['borrower_id'])->find();
        //已用额度+订单金额>总额度
        $user['money1']=bcadd($user['money1'],$info['money'],2);
        if( $user['money1']>$user['money0']){
            $this->error('额度不足，不能订单');
        }
        if($data['status']==4){
            $data['money_time']=time();
            $data['start_time']=zz_get_time0();
            $data['end_time']=$data['start_time']+$info['expire_day']*3600*24; 
            $data_user=[
                'money1'=>$user['money1'],
                'borrow_num'=>$user['borrow_num']+1,
                'borrow_money'=>bcadd($user['borrow_money'],$info['money'],2),
            ];
            
        } 
        Db::startTrans();
        $m_user->where('id',$info['borrower_id'])->update($data_user);
        $m->where('id',$data['id'])->update($data);
        Db::name('action')->insert($data_action);
        Db::commit();
        
       
        
        $this->success('保存成功！',url('index'));
         
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
            'money1'=>$data['money1'],
            'price1'=>$data['price1'],
            'time'=>time(),
        ];
        $statuss=$this->order_status;
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'order',
            'key'=>$info['oid'],
            'time'=>$data_order['time'],
            'ip'=>get_client_ip(),
            'action'=>'对订单'.$info['oid'].'处理结果为'.$statuss[$data['status']],
            
        ];
        
        
         
        Db::startTrans();
       
        $m->where('id',$data['id'])->update($data_order);
        Db::name('action')->insert($data_action);
        Db::commit();
        
        
        
        $this->success('保存成功！',url('index'));
        
    }
    
}
