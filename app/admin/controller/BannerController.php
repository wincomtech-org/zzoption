<?php

 
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
 
 
class BannerController extends AdminbaseController {

    private $m;
    private $order;
   private $type_info;
    public function _initialize()
    {
        parent::_initialize(); 
        $this->order='sort asc,id asc';
        $this->m=Db::name('Banner');
        
        $this->assign('flag','Banner图');
    }
    
    /**
     * Banner图列表
     * @adminMenu(
     *     'name'   => 'Banner图管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => 'Banner图管理',
     *     'param'  => ''
     * )
     */
    function index(){
        $m=$this->m;

         $list= $m->order($this->order)->paginate(10);
         // 获取分页显示
         $page = $list->render(); 
          
         $this->assign('page',$page);
         
         $this->assign('list',$list);
         
        return $this->fetch();
    }
    /**
     * Banner图编辑
     * @adminMenu(
     *     'name'   => 'Banner图编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => 'Banner图编辑',
     *     'param'  => ''
     * )
     */
    function edit(){
        $m=$this->m;
        $id=$this->request->param('id'); 
        $info=$m->where('id',$id)->find(); 
      
        $this->assign('info',$info);
       
        
        //不同类别到不同的页面
        return $this->fetch();
    }
    /**
     * Banner图编辑1
     * @adminMenu(
     *     'name'   => 'Banner图编辑1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => 'Banner图编辑1',
     *     'param'  => ''
     * )
     */
    function editPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
        
        if(empty($data['pic']) || !is_file(getcwd().'/upload/'.$data['pic'])){
            $this->error('必须要有图片');
        }
        zz_set_image($data['pic'], $data['pic'], 640, 260);
        //处理网址，补加http:// 
        $data['link']=zz_link($data['link']);
        $data['time']=time();
        $row=$m->where('id', $data['id'])->update($data);
        if($row===1){
            $this->success('修改成功',url('index')); 
        }else{
            $this->error('修改失败');
        }
        
    }
    /**
     * Banner图删除
     * @adminMenu(
     *     'name'   => 'Banner图删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => 'Banner图删除',
     *     'param'  => ''
     * )
     */
    function delete(){
        $m=$this->m;
         $id = $this->request->param('id', 0, 'intval');
        $row=$m->where('id',$id)->delete();
        if($row===1){ 
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;
    }
   
    /**
     * Banner图添加
     * @adminMenu(
     *     'name'   => 'Banner图添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => 'Banner图添加',
     *     'param'  => ''
     * )
     */
    public function add(){
        
        return $this->fetch();
    }
    
    /**
     * Banner图添加1
     * @adminMenu(
     *     'name'   => 'Banner图添加1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => 'Banner图添加1',
     *     'param'  => ''
     * )
     */
    public function addPost(){
        
        $m=$this->m; 
        $data= $this->request->param();
        if(empty($data['pic']) || !is_file(getcwd().'/upload/'.$data['pic'])){
            $this->error('必须要有图片');
        }
        zz_set_image($data['pic'], $data['pic'], 640, 260);
        $data['time']=time();
        $row=$m->insertGetId($data);
        if($row>=1){
            $this->success('已成功添加',url('index')); 
        }else{
            $this->error('添加失败');
        }
        exit;
    }
}

?>