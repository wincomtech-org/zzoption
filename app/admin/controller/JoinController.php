<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class JoinController extends AdminBaseController
{
    private $m;
    private $shop_type;
    private $join_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('join');
        
        $this->join_status=config('join_status');
        $this->assign('flag','加盟申请');
        $types=config('shop_type');
        unset($types[0]);
        $this->shop_type=$types;
        $this->assign('join_status', $this->join_status);
        $this->assign('shop_type', $this->shop_type);
        $this->assign('website',config('website'));
    }
     
    /**
     * 加盟申请列表
     * @adminMenu(
     *     'name'   => '加盟申请列表',
     *     'parent' => 'admin/shop/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 5,
     *     'icon'   => '',
     *     'remark' => '加盟申请列表',
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
            $whereOr['uname|name']=['like','%'.$data['name'].'%'];
        }
        //主站显示所有,分站显示自己网站的申请
        $shopid=session('shop.id');
       
        if($shopid!=1){ 
            $where['shop']=['eq',$shopid];
        }
        $list= $m->where($where)->whereOr($whereOr)->order('shop asc,time desc')->paginate(10);
      
        // 获取分页显示
        $page = $list->appends($data)->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 加盟申请查看
     * @adminMenu(
     *     'name'   => '加盟申请查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟申请查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此加盟申请不存在或已失效');
        }
       
        
        $this->assign('info',$info); 
        
        return $this->fetch();
    }
    /**
     * 加盟申请编辑执行
     * @adminMenu(
     *     'name'   => '加盟申请编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟申请编辑执行',
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
       
        $join=session('shop');
        if($join['id']!=$info['shop'] && $join['id']!=1){ 
            $this->error('只有总站和上级才能编辑加盟信息');
        }
        $statuss=$this->join_status;
        $data=$data0;
        
        $data['time']=time();
          
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'join',
            'key'=>$info['id'],
            'time'=>$data['time'],
            'ip'=>get_client_ip(),
            'action'=>'对加盟申请'.$info['company'].'编辑',
            
        ];
        $m->where($where)->update($data);
         
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
         
    }
     
    /**
     * 加盟申请添加
     * @adminMenu(
     *     'name'   => '加盟申请添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟申请添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $m=$this->m;
         $join=session('shop');
         $tmp=[];
         $types=$this->shop_type;
         //只能添加比自己等级低的代理
         foreach ($types as $k=>$v){
             if($k > $join['type']){
                 $tmp[$k]=$v;
             }
         }
        
         $this->assign('join0',$join);
         $this->assign('shop_type',$tmp);
        return $this->fetch();
    }
    /**
     * 加盟申请添加执行
     * @adminMenu(
     *     'name'   => '加盟申请添加执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟申请添加执行',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        
        $m=$this->m;
        $data0=$this->request->param();
       
        
        //判断管理员权限 
        $join0=session('shop');
        
        $data=$data0;
        if($join0['type']>1 || $join0['type']>$data['type']){
            $this->error('只能添加比自己级别低的分站');
        } 
        $data['shop']= $join0['id'];
        $data['time']=time();
        $data['insert_time']=$data['time'];
        $id=$m->insertGetId($data);
       
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'join',
            'key'=>$id,
            'time'=>$data['time'],
            'ip'=>get_client_ip(),
            'action'=>'添加加盟申请'.$data['company'], 
        ];
      
        
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
        
    }
    
}
