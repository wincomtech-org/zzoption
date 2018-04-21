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
  
class IndexController extends HomeBaseController
{
    public function _initialize()
    {
        
        parent::_initialize();
    }
    /* 首页 */
    public function index(){
        
       //获取分站信息
        $shop=session('shop');
        $banners=Db::name('banner')->where('shop',$shop['id'])->order('sort asc')->column('id,pic,link');
        //最新订单
        $orders=Db::name('order')
        ->order('buy_time desc')
        ->where('status','in','3,4,6,7,8')
        ->limit('5')
        ->column('id,uname,money0,name,code0,month,status');
       
        //获取最新指数
        $indices=Db::name('stock_indice')->column('id,name,count,num,percent');
        //获取最新资讯
        $news=Db::name('stock_news')->where('shop','in',[0,$shop['aid']])->order('list_order asc,create_time desc')->limit(5)->column('id,title,source,create_time');
        //获取未读消息
        $uid=session('user.id');
        if(!empty($uid)){
            $where_msg=[
                'uid'=>['eq',$uid],
                'status'=>['eq',1]
            ];
            $msg=Db::name('msg')->field('id')->where($where_msg)->find();
            $this->assign('msg',$msg);
        }
        $this->assign('html_title','首页');
        $this->assign('html_flag','index');
        $this->assign('banners',$banners);
        $this->assign('orders',$orders);
        $this->assign('indices',$indices);
        $this->assign('news',$news);
        return $this->fetch();
    }
     
    /* 前台我的信息*/
    public function my(){
       
        $user=session('user');
         if(empty($user)){
             $user=[
                 'user_nickname'=>'未登录',
                 'avatar'=>'avatar.jpg'
             ];
         } 
         $this->assign('html_title','个人中心');
         $this->assign('html_flag','my');
         $this->assign('user',$user);
        return $this->fetch();
    }
    
    /* 关于我们*/
    public function about(){
        
        $shop=session('shop');
        $this->assign('html_title','关于我们');
        $this->assign('shop',$shop);
        return $this->fetch();
    }
    /* 风险*/
    public function point(){
        
        $point=Db::name('about')->where('type','point')->find();
        $this->assign('html_title','风险');
        $this->assign('point',$point);
        return $this->fetch();
    }
    /* 询价*/
    public function transcation(){
        $name=$this->request->param('code','','trim');
        //登录用户跳转
        if(!empty(session('user'))){
            $this->redirect(url('user/trade/index',['code'=>$name]));
        }
        if(!empty($name)){
            $whereOr=[
              'code|code0|name'=>['eq',$name],  
            ];
            $stock=Db::name('stock')->whereOr($whereOr)->find(); 
        }
        if(empty($stock)){
            $this->assign('code',$name);
        }else{
            $this->assign('stock',$stock);
        }
        
        
        $guide=Db::name('guide')->where('name','trade')->find();
        $this->assign('html_title','交易');
        $this->assign('html_flag','trade');
        $this->assign('guide',$guide['title']);
        $this->assign('day',config('day'));
       
        $this->assign('money_off',config('money_off'));
        $this->assign('money_on',config('money_on'));
        return $this->fetch();
    }
    
     
     
}
