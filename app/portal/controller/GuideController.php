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

class GuideController extends HomeBaseController
{
    private $m;
    private $types;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('guide');
        $this->types=config('guide_types');
    }
     
     
    /**
     * 协议
     */
    public function agreement()
    {
        $name=$this->request->param('name','');
        $m=$this->m;
        
        $info=$m->where(['type'=>1,'name'=>$name])->find();
        if(empty($info)){
            $this->error('无此协议');
        }
        $this->assign('info',$info);
        $this->assign('html_title',$info['title']);
        return $this->fetch();
        
    }
     
    /**
     * 新手课堂
     */
    public function help()
    {
        
        $m=$this->m;
        $types=$this->types;
        $names=$m->where(['type'=>2])->order('sort asc')->column('id,name,title');
         
        $list=[];
        foreach($names as $k=>$v){
            $list[$v['name']][]=['id'=>$v['id'],'title'=>$v['title']]; 
        } 
        $this->assign('list',$list);
        $this->assign('html_title',$types[2]);
        return $this->fetch();
        
    }
    /**
     * 详情
     */
    public function info()
    {
        
        $m=$this->m;
        $types=$this->types;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        
        $this->assign('info',$info);
        $this->assign('html_title',$types[2]);
        return $this->fetch();
        
    }
    
}
