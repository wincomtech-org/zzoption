<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class PaperOldController extends AdminBaseController
{
    private $m;
    private $order;
    private $paper_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('paper_old');
        $this->order='id desc';
        $this->paper_status=config('paper_status');
        $this->assign('flag','已完成借款');
        
        $this->assign('paper_status', $this->paper_status);
    }
     
    /**
     * 已完成借款列表
     * @adminMenu(
     *     'name'   => '已完成借款列表',
     *     'parent' => 'admin/Paper/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '已完成借款列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        
        if(empty($data['borrower_idcard'])){
            $data['borrower_idcard']='';
        }else{
            $where['borrower_idcard']=$data['borrower_idcard'];
        }
       
        
        $list= $m->where($where)->order($this->order)->paginate(10);
       
        // 获取分页显示
        $page = $list->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 已完成借款查看
     * @adminMenu(
     *     'name'   => '已完成借款查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '已完成借款查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此借款不存在');
        }
        $this->assign('info',$info); 
        return $this->fetch();
    }
    /**
     * 已完成借款编辑执行
     * @adminMenu(
     *     'name'   => '已完成借款编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '已完成借款编辑执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $this->error('暂时不能修改已还款借款');
         
    }
    
}
