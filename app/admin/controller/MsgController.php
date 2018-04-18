<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class MsgController extends AdminBaseController
{
    private $m;
    private $msg;
    private $msg_status;
    private $msg_types;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('msg');
        $this->msg='time desc';
        $this->msg_status=config('msg_status');
        $this->msg_types=config('msg_type');
        $this->assign('flag','消息');
        $this->assign('msg_status', $this->msg_status);
        $this->assign('msg_types', $this->msg_types);
    }
    
    /**
     * 站内消息
     * @adminMenu(
     *     'name'   => '消息列表',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '消息列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(empty($data['status'])){
            $data['status']=0; 
        }else{
            $where['m.status']=$data['status'];
        }
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['mt.type']=$data['type'];
        }
        
        if(empty($data['mobile'])){
            $data['mobile']='';
        }else{
            $where['u.mobile']=$data['mobile'];
        }
        $list=$m
        ->field('m.*,mt.title,mt.type,mt.time,u.user_nickname as uname,u.mobile,a.user_nickname as aname')
        ->alias('m')
        ->join('cmf_msg_txt mt','mt.id=m.msg_id','left')
        ->join('cmf_user u','u.id=m.uid','left')
        ->join('cmf_user a','a.id=mt.aid','left')
        ->where($where)
        ->order('mt.time desc')->paginate(10);
        
        // 获取分页显示
        $page = $list->appends($data)->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 消息查看
     * @adminMenu(
     *     'name'   => '消息查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '消息查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->field('m.*,mt.title,mt.type,mt.content,mt.time,u.user_nickname as uname,u.mobile,a.user_nickname as aname')
        ->alias('m')
        ->join('cmf_msg_txt mt','mt.id=m.msg_id','left')
        ->join('cmf_user u','u.id=m.uid','left')
        ->join('cmf_user a','a.id=mt.aid','left')
        ->where(['m.id'=>$id])
        ->find();
        if(empty($info)){
            $this->error('此消息不存在');
        }
       
        $this->assign('info',$info); 
        return $this->fetch();
    }
    /**
     * 消息发送
     * @adminMenu(
     *     'name'   => '消息发送',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '消息发送',
     *     'param'  => ''
     * )
     */
    public function send()
    {
        
        return $this->fetch();
        
    }
    /**
     * 消息发送获取用户
     * @adminMenu(
     *     'name'   => '消息发送获取用户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '消息发送获取用户',
     *     'param'  => ''
     * )
     */
    public function ajax_search()
    {
        $data=$this->request->param();
        $where=[
            'user_type'=>['eq',2],
            'user_status'=>['eq',1],
            $data['select']=>['like','%'.$data['info'].'%']
        ];
        $users=Db::name('user')->where($where)->column('id,user_nickname,mobile');
        $this->success('搜索成功','',$users);
        
    }
    /**
     * 消息发送
     * @adminMenu(
     *     'name'   => '消息发送',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '消息发送',
     *     'param'  => ''
     * )
     */
    public function send_do()
    {
        $data=$this->request->param();
        //系统消息未选择用户时默认发送给所有用户
        if(empty($data['uids'])){
            if($data['type']==1){
                $where=[
                    'user_type'=>['eq',2],
                    'user_status'=>['eq',1],
                ];
                $data['uids']=$users=Db::name('user')->where($where)->column('id');
            }else{
                $this->error('发送个人消息必须选择用户');
            }
            
        } 
        $aid=session('ADMIN_ID');
        //先保存消息内容再保存用户消息连接
        $data_txt=[
            'aid'=>$aid,
            'title'=>$data['title'],
            'content'=>$data['content'],
            'type'=>$data['type'],
            'time'=>time(),
        ];
        $msg_id=Db::name('msg_txt')->insertGetId($data_txt);
       
        foreach($data['uids'] as $v){
            $data_msg[]=[ 
                'msg_id'=>$msg_id,
                'uid'=>$v
            ];
        }
        $m=$this->m;
        $m->insertAll($data_msg);
        $this->success('发送成功！');
        
    }
    
}
