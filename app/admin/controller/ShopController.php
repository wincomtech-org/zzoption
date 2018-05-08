<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
/**
 * Class ShopController
 * @package app\admin\controller
 *
* @adminMenuRoot(
 *     'name'   =>'分站管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'',
 *     'remark' =>'分站管理'
 * )
 *
 */
class ShopController extends AdminBaseController
{
    private $m;
    private $shop_type;
    private $shop_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('shop');
        
        $this->shop_status=config('shop_status');
        $this->assign('flag','分站代理');
        $this->shop_type=config('shop_type');
        $this->assign('shop_status', $this->shop_status);
        $this->assign('shop_type', $this->shop_type);
        $this->assign('website',config('website'));
    }
     
    /**
     * 分站代理列表
     * @adminMenu(
     *     'name'   => '分站代理列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
       
        $m=$this->m;
        $where=[];
        $whereOr=[];
        $data=$this->request->param();
        if(isset($data['type']) &&  $data['type']!='-1'){
            $where['type']=['eq',$data['type']];
        }else{
            $data['type']='-1'; 
        }
        if(empty($data['status'])){
            $data['status']=0; 
        }else{
            $where['status']=['eq',$data['status']];
        }
        if(empty($data['name'])){
            $data['name']='';
        }else{
            $whereOr['code|title|name']=['like','%'.$data['name'].'%'];
        }
        //主站显示所有和分站显示自己和子级
        $shopid=session('shop.id');
       
        if($shopid!=1){
            $ids=$m->where('fid',$shopid)->column('id');
            $ids[]=$shopid;
            $where['id']=['in',$ids];
        }
        $list= $m->where($where)->whereOr($whereOr)->order('fpath asc,id asc')->paginate(10);
      
        // 获取分页显示
        $page = $list->appends($data)->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 分站代理查看
     * @adminMenu(
     *     'name'   => '分站代理查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此分站代理不存在或已失效');
        }
        $shop=session('shop');
       
        if($shop['id']==$info['id']){
            zz_shop(['id'=>$info['id']]);
        } 
      
        $this->assign('info',$info); 
        
        return $this->fetch();
    }
    /**
     * 分站代理编辑执行
     * @adminMenu(
     *     'name'   => '分站代理编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理编辑执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        
        $m=$this->m; 
        $data0=$this->request->param();
        $where=['id'=>$data0['id']];
        $info=$m->where($where)->find();
        if(empty($info) ){
            $this->error('分站不存在');
        }
        //判断管理员权限
       
        $shop=session('shop');
        if($shop['id']!=$info['id'] && $shop['id']!=1){ 
            $this->error('只有总站和自己才能编辑分站信息');
        }
        $statuss=$this->shop_status;
        $data=$data0;
        if($info['type']!=2){
             
            $data['logo']=zz_picid($data['logo'],$info['logo'],'logo',$info['id']);
            //logo处理
            if(empty($data['logo'])){
                $this->error('logo图片不存在');
            }
            $data['qrcode']=zz_picid($data['qrcode'],$info['qrcode'],'qrcode',$info['id']);
            if(empty($data['qrcode'])){
                $this->error('二维码图片不存在');
            } 
        }
        $data['time']=time();
          
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'shop',
            'key'=>$info['code'],
            'time'=>$data['time'],
            'ip'=>get_client_ip(),
            'action'=>'对分站代理'.$info['code'].'-'.$info['name'].'编辑',
            
        ];
        $m->where($where)->update($data);
        //如果是修改自己，要更新session
        if($shop['id']==$info['id']){
           zz_shop(['id'=>$shop['id']]);
        }
       
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
         
    }
    /**
     * 分站代理设置
     * @adminMenu(
     *     'name'   => '分站代理设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理设置',
     *     'param'  => ''
     * )
     */
    public function set()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此分站代理不存在或已失效');
        }
        
        $this->assign('info',$info);
        
        return $this->fetch();
    }
    /**
     * 分站代理设置执行
     * @adminMenu(
     *     'name'   => '分站代理设置执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理设置执行',
     *     'param'  => ''
     * )
     */
    public function setPost()
    {
        
        $m=$this->m;
        $data0=$this->request->param();
        $where=['id'=>$data0['id']];
        $info=$m->where($where)->find();     
        if(empty($info) ){
            $this->error('分站不存在');
        }
       
        
        //判断管理员权限
        $shop0=session('shop');
        if($shop0['id']!=1 && $shop0['id']!=$info['fid']){
            $this->error('只有总站和直属上级才能设置下级分站');
        }
        
        $statuss=$this->shop_status;
        $data=[
            'time'=>time(),
            'rate'=>round($data0['rate'],4),
            'status'=>intval($data0['status']),
        ];
        if($shop0['rate']<$data['rate']){
            $this->error('下级分站的提成比例必须低于上级');
         }
        $data['time']=time();
        
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'shop',
            'key'=>$info['code'],
            'time'=>$data['time'],
            'ip'=>get_client_ip(),
            'action'=>'对分站代理'.$info['code'].'-'.$info['name'].'设置',
            
        ];
        $m->where($where)->update($data);
        
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
        
    }
    /**
     * 分站代理添加
     * @adminMenu(
     *     'name'   => '分站代理添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $m=$this->m;
         $shop=session('shop');
         $tmp=[];
         $types=$this->shop_type;
         //只能添加比自己等级低的代理
         foreach ($types as $k=>$v){
             if($k > $shop['type']){
                 $tmp[$k]=$v;
             }
         }
        
         $this->assign('shop0',$shop);
         $this->assign('shop_type',$tmp);
        return $this->fetch();
    }
    /**
     * 分站代理添加执行
     * @adminMenu(
     *     'name'   => '分站代理添加执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '分站代理添加执行',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        
        $m=$this->m;
        $data0=$this->request->param();
       
        
        //判断管理员权限 
        $shop0=session('shop');
        $data=[
            'time'=>time(),
            'name'=>$data0['name'],
            'website'=>$data0['website'],
            'type'=>intval($data0['type']),
            'rate'=>$data0['rate'],
            'fid'=>$shop0['id'],
            'code'=>0,
        ];
        if($shop0['type']>1 || $shop0['type']>$data['type']){
            $this->error('只能添加比自己级别低的分站');
        } 
        if($shop0['rate']<$data['rate']){
            $this->error('下级分站的提成比例必须低于上级');
        }
        $tmp=$m->where('website',$data['website'])->find();
        if(!empty($tmp)){
            $this->error('该二级域名已被使用');
        }
        $id=$m->insertGetId($data);
        $code=$id+10000;
        $data_update=[
            'code'=>$code,
            'fpath'=>$shop0['fpath'].'-'.$id, 
        ];
        
        $m->where('id',$id)->update($data_update);
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'shop',
            'key'=>$code,
            'time'=>$data['time'],
            'ip'=>get_client_ip(),
            'action'=>'添加分站代理'.$code.'-'.$data['name'], 
        ];
      
        
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
        
    }
    
}
