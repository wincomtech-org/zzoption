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
        
        //购买订单
        $where_order=['status'=>['in',[4,6,7,8]]];
        $where_user=[];
        $m1=Db::name('order');
       
        $m_user=Db::name('user');
        $count1=[];
        
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
             
            // 买入期权
            $where_order['have_time']=array('between',array($times[$i],$times[$i+1]));
            $count1['order'][$i]=$m1->where($where_order)->count();
            $count1['money'][$i]=$m1->where($where_order)->sum('money2');
            if(empty($count1['money'][$i])){
                $count1['money'][$i]=0;
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
        
        $where_order['have_time']=array('between',array($times[1],$times[13]));
        $count1['order'][0]=$m1->where($where_order)->count();
        $count1['money'][0]=$m1->where($where_order)->sum('money2');
        if(empty($count1['money'][0])){
            $count1['money'][0]=0;
        }
         
        $this->assign('labels',$labels);
        $this->assign('count1',$count1);
       
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
            
            $where['shop']=['eq',$data['shop']];
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
    
    /**
     * 分站统计
     * @adminMenu(
     *     'name'   => '分站统计',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站统计',
     *     'param'  => ''
     * )
     */
    public function money()
    {
        
        $data=$this->request->param();
        $where=[];
        //根据时间查找
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
                    $where['o.have_time']=['between',[$start_time0,$end_time0]];
                }
            }else{
                $where['o.have_time']=['egt',$start_time0];
            }
        }elseif(isset($end_time0)){
            $where['o.have_time']=['elt',$end_time0];
        }
        
         
        //已买入期权
        $where['o.status']=['in',[4,6,7,8]];
        
       $list=Db::name('shop')
       ->field('s.id,s.name,s.rate,s.type,s.status,s.code,s.fid,sum(o.money1) as moneys,count(o.id) as counts')
       ->alias('s')
       ->join('order o','s.id=o.shop','left')
       ->where($where)
       ->order('s.fid asc,s.id asc')
       ->select();
       //"385985.00"
       //计算上下级提成
       $tmp=[];
       //统计总数
       $count['moneys']=0;
       $count['counts']=0;
       $count['moneys1']=0;
       $count['moneys2']=0;
       $count['moneys3']=0;
        
       foreach($list as $k=>$v){
           $tmp[$v['id']]=$v;
           $count['counts']+=$v['counts'];
           $count['moneys']+=$v['moneys'];
           //自己分站的提成//如果不是总站，计算提成
           if($v['id']==1){
               $tmp[$v['id']]['moneys1']=0;
           }else{
               $tmp[$v['id']]['moneys1']=bcmul($v['moneys'],$v['rate'],2);
           }
           $count['moneys1']+=$tmp[$v['id']]['moneys1'];
           $count['moneys3']+=$count['moneys1'];
           //分站子站的提成
           $tmp[$v['id']]['moneys2']=0; 
           $tmp[$v['id']]['moneys3']=$tmp[$v['id']]['moneys1'];
           //如果不是总站，计算分站提成
           if($v['fid']!=1){
               $rate_sub=bcsub($tmp[$v['fid']]['rate'],$v['rate'],4);
               $money_sub=bcmul($rate_sub,$v['moneys'],2);
               $tmp[$v['fid']]['moneys2']+=$money_sub;
               $tmp[$v['fid']]['moneys3']+=$money_sub;
               $count['moneys2']+=$money_sub;
               $count['moneys3']+=$money_sub;
           }
       }
        
        $this->assign('shop_status',config('shop_status'));
        $this->assign('shop_types',config('shop_types'));
        $this->assign('data',$data);
        $this->assign('list',$tmp);
        $this->assign('count',$count); 
        return $this->fetch();
    }
    
    
    
}
