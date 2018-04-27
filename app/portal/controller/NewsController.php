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

class NewsController extends HomeBaseController
{
    private $m;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('stock_news');
        $this->assign('html_title','资讯');
        $this->assign('html_flag','news');
    } 
    /**
     * 新闻资讯
     */
    public function index()
    {
        
        $m=$this->m;
        $cid=$this->request->param('cid',0,'intval');
        $cates=Db::name('stock_news_category')->order('list_order asc')->column('id,name');
        
        if($cid==0){
           $cid=key($cates);
        }
        $where=[
            'cate_id'=>['eq',$cid],
            'shop'=>['in',[0,session('shop.aid')]],
        ];
        $list=$m->where($where)->order('list_order asc,create_time desc')->column('');
       
        $this->assign('list',$list);
        $this->assign('cates',$cates);
        $this->assign('cid',$cid);
       
        return $this->fetch();
        
    }
    /**
     * 详情
     */
    public function info()
    {
        
        $m=$this->m;
      
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        $info['content'] = cmf_replace_content_file_url(htmlspecialchars_decode($info['content']));
        
        $this->assign('info',$info);
       
        return $this->fetch();
        
    }
    
}
