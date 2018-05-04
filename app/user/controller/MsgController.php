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
namespace app\user\controller;

use cmf\controller\UserBaseController;
use think\Db;

class MsgController extends UserBaseController
{
    private $m;
    private $types;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('msg');
        $this->types=config('msg_type');
        $this->assign('html_title','我的消息');
        $this->assign('html_flag','msg');
    } 
    /**
     *我的消息
     */
    public function index()
    {
        
        $types=$this->types;
        $m=$this->m;
        $where=[
            'm.uid'=>session('user.id'),
            'm.status'=>1
        ];
        $list=[];
        foreach($types as $k=>$v){
            $where['mt.type']=$k;
            $tmp=$m
            ->alias('m')
            ->join('cmf_msg_txt mt','mt.id=m.msg_id')
            ->where($where)
            ->find();
            $list[$k]=['name'=>$v];
            if(empty($tmp)){
                $list[$k]['noread']=0;
            }else{
                $list[$k]['noread']=1;
            }
        }
        $this->assign('list',$list);
        return $this->fetch();
        
    }
    /**
     *我的消息列表
     */
    public function lists()
    {
        $types=$this->types;
        $m=$this->m;
        $type=$this->request->param('type',1,'intval');
        $where=[
            'm.uid'=>session('user.id'),
            'mt.type'=>$type
        ];
        //得到所有消息
        $list=$m
        ->alias('m')
        ->join('cmf_msg_txt mt','mt.id=m.msg_id')
        ->where($where)
        ->order('mt.time desc')->column('');
        //消息设为已读
        $where['m.status']=1;
        $m->alias('m')
        ->join('cmf_msg_txt mt','mt.id=m.msg_id')
        ->where($where)
        ->update(['m.status'=>2]);
        $this->assign('list',$list);
        $this->assign('html_title',$types[$type]);
        return $this->fetch();
        
    }
    
}
