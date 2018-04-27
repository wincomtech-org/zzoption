<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController;
use app\admin\model\CateModel;
use think\Db;

 
/**
 * Class CountController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '数据统计',
 *     'action' => 'default',
 *     'parent' => '',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   => '',
 *     'remark' => '数据统计'
 * )
 */
class CountController extends AdminBaseController
{
  
    public function _initialize()
    {
        parent::_initialize();
        
    }
     
    /**
     * 12月统计 
     * @adminMenu(
     *     'name'   => '12月统计',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '12月统计',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        exit('ff');
        //新增未完成借款
        $where_new=['status'=>['in',[3,4,5]]];
        $where_user=['user_type'=>['eq',2]];
        $m1=Db::name('order');
        $m2=Db::name('order_old');
        $m_user=Db::name('user');
        $count1=[];
        $count2=[];
        $users=[];
        //计算月份
        $time=time();
        $date=getdate($time);
        $year=$date['year'];
        $mon=$date['mon'];
        
        
        $times[13]=$time;
        
        
        //计算前12个月每月的数据
        for($i=12;$i>0;$i--){
            
            $labels[$i]=$year.'-'.$mon;
            //stime用于datetime格式的数据库计算
            $stime=$labels[$i].'-01';
            if($i==12){
                $stime1=date('Y-m-d',$time);
            }else{
                $stime1=$labels[$i+1].'-01';
            }
            
            $times[$i]=strtotime($stime);
            $where_user['create_time']=['between',[$times[$i],$times[$i+1]]]; 
            $users[$i]=$m_user->where($where_user)->count();
             
            // 未还款借款借款
            $where_new['money_time']=array('between',array($times[$i],$times[$i+1]));
            $count1['order'][$i]=$m1->where($where_new)->count();
            $count1['money'][$i]=$m1->where($where_new)->sum('money');
            if(empty($count1['money'][$i])){
                $count1['money'][$i]=0;
            }
            //已还款借款
            $where_new1=['money_time'=>array('between',array($times[$i],$times[$i+1]))];
            $tmp1=$m2->where($where_new1)->count();
            $tmp2=$m2->where($where_new1)->sum('money');
            if(empty($tmp2)){
                $tmp2=0;
            }
            //已还款和未还款的借款相加
            $count1['order'][$i]+=$tmp1;
            $count1['money'][$i]+=$tmp2;
            //还款
            $where_old=['update_time'=>array('between',array($times[$i],$times[$i+1]))];
            $count2['order'][$i]=$m2->where($where_old)->count();
            $count2['money'][$i]=$m2->where($where_old)->sum('final_money');
            if(empty($count2['money'][$i])){
                $count2['money'][$i]=0;
            }
            
            $mon--;
            if($mon==0){
                $year--;
                $mon=12;
            }
        }
      
        //总订单，总用户
        $where_user['create_time']=['between',[$times[1],$times[13]]]; 
        $users[0]=$m_user->where($where_user)->count();
        
        $where_new['insert_time']=array('between',array($times[1],$times[13]));
        $count1['order'][0]=$m1->where($where_new)->count();
        $count1['money'][0]=$m1->where($where_new)->sum('money');
        $where_new1=['insert_time'=>array('between',array($times[1],$times[13]))];
        $tmp1=$m2->where($where_new1)->count();
        $tmp2=$m2->where($where_new1)->sum('money');
        if(empty($count1['money'][0])){
            $count1['money'][0]=0;
        }
        if(empty($tmp2)){
            $tmp2=0;
        }
        //已还款和未还款的借款相加
        $count1['order'][0]+=$tmp1;
        $count1['money'][0]+=$tmp2;
        
        $where_old=['update_time'=>array('between',array($times[1],$times[13]))];
        $count2['order'][0]=$m2->where($where_old)->count();
        $count2['money'][0]=$m2->where($where_old)->sum('final_money');
        if(empty($count2['money'][0])){
            $count2['money'][0]=0;
        }
         
        $this->assign('labels',$labels);
        $this->assign('count1',$count1);
        $this->assign('count2',$count2);
        $this->assign('users',$users);
        return $this->fetch();
    }
    /**
     * 统计查询
     * @adminMenu(
     *     'name'   => '统计查询',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '统计查询',
     *     'param'  => ''
     * )
     */
    public function search()
    {
      
        $data=$this->request->param();
        $where=[];
        $m1=Db::name('order'); 
        $m_user=Db::name('user');
        $count=[]; 
        $where_user=['user_type'=>2];
        if(empty($data['shop'])){
            $data['shop']='';
        }else{
            $where_user['shop']=$data['shop'];
            $uids=$m_user->where($where_user)->column('id');
            $where['uid']=['in',$uids];
        }
        if(empty($data['uid'])){
            $data['uid']='';
        }else{
            $where['uid']=['eq',$data['uid']];
        }
        if(empty($data['code'])){
            $data['code']='';
        }else{
            $where['code']=['eq',$data['code']];
        }
         
        if(empty($data['start_time'] )){
            $data['start_time']='';
        }else{
            $data['start_time']=$data['start_time'];
            $start_time0=strtotime($data['start_time']);
        }
        if(empty($data['end_time'] )){
            $data['end_time']='';
        }else{
            $data['end_time']=$data['end_time'];
            $end_time0=strtotime($data['end_time']);
        }
        
        if(isset($start_time0)){
            if(isset($end_time0)){
                if($start_time0>=$end_time0){
                    $this->error('起始时间不能大于等于结束时间',url('search'));
                }else{
                    $where['have_time']=['between',[$start_time0,$end_time0]];
                }
            }else{
                $where['have_time']=['egt',$start_time0];
            }
        }elseif(isset($end_time0)){
            $where['have_time']=['elt',$end_time0];
        }
       
       
        
        
        //3正在出借,4今日到期,5逾期
        /* 'order_status' =>
        array (
            0 => '询价中',
            1 => '询价成功',
            2 => '询价失败',
            3 => '已付款',
            4 => '持仓中',
            5 => '买入失败',
            6 => '行权中',
            7 => '行权结束',
            8 => '行权过期', */
        //已付款，未结束
        $where['status']=['in',[3,4,6]]; 
        $count['buy_count']=$m1->where($where)->count();
        $count['buy_money']=$m1->where($where)->sum('money1');
        //用户数
        if(!empty($where['have_time'])){
            $where_user['create_time']=$where['have_time'];
            $where['sell_time']=$where['have_time'];
            unset($where['sell_time']);
        }
        
        $count['user']=$m_user->where($where_user)->count();
        //已行权结束
        $where['status']=['in',['7,8']];
        $count['sell_count']=$m1->where($where)->count();
        $count['sell_money1']=$m1->where($where)->sum('money1'); 
        $count['sell_money2']=$m1->where($where)->sum('money2');
        
        $shops= Db::name('shop')->order('fid asc,id asc')->column('id,code,name,status');
       
        $this->assign('shops',$shops);
        $this->assign('shop_status',config('shop_status'));
        $this->assign('data',$data);
        $this->assign('count',$count);
        
        return $this->fetch();
    }
    
    
    
}
