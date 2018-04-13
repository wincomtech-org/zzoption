<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class ReplyController extends AdminBaseController
{
    private $m;
   
    private $reply_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('reply');
        
        $this->reply_status=config('reply_status');
        $this->assign('flag','还款申请');
        
        $this->assign('reply_status', $this->reply_status);
    }
     
    /**
     * 还款申请列表
     * @adminMenu(
     *     'name'   => '还款申请列表',
     *     'parent' => 'admin/Paper/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '还款申请列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(isset($data['status']) &&  $data['status']!='-1'){
            $where['r.status']=$data['status'];
        }else{
            $data['status']='-1'; 
        }
        
        
        $list= $m
        ->alias('r') 
        ->where($where)
        ->order('r.id desc')
        ->paginate(10);
       
        // 获取分页显示
        $page = $list->render(); 
       //得到所有管理员
       
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        
        return $this->fetch();
    }
    /**
     * 还款申请查看
     * @adminMenu(
     *     'name'   => '还款申请查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '还款申请查看',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此还款申请不存在或已失效');
        }
        $info1= Db::name('paper')->where('oid',$info['oid'])->find();
         
        if(empty($info1)){
            $info1=Db::name('paper_old')->where('oid',$info['oid'])->find(); 
            if(empty($info1)){
                $this->error('此还款申请关联的信息错误');
            }
            $info1['status']=6;
        }
       
        $this->assign('info1',$info1); 
        $this->assign('info',$info); 
        $this->assign('paper_status', config('paper_status'));
        return $this->fetch();
    }
    /**
     * 还款申请编辑执行
     * @adminMenu(
     *     'name'   => '还款申请编辑执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '还款申请编辑执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $tmp=zz_check_time();
        if($tmp[0]===1){
            $this->error($tmp[1]);
        }
        $m=$this->m; 
        $data0=$this->request->param();
        $where=['id'=>$data0['id']];
        $info=$m->where($where)->find();
        if(empty($info) || $info['status']!=0 || $info['is_overtime']!=0){
            $this->error('不能处理已过期或确认过的还款申请');
        }
        $m_paper=Db::name('paper');
        $info1= $m_paper->where('oid',$info['oid'])->find();
        
        if(empty($info1) || $info1['status']<=2){
            $this->error('借款已完成或借款信息错误');
        }
        $statuss=$this->reply_status;
        
        $data=[
            'update_time'=>time(),
            'status'=>$data0['status'],
            'dsc'=>$data0['dsc']
        ];
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'paper',
            'time'=>$data['update_time'],
            'ip'=>get_client_ip(),
            'action'=>'对借款'.$info['oid'].'的还款申请'.$info['id'].'更改状态为"'.$statuss[$data['status']].'"',
            
        ];
       
        //如果是确认还款结束的就进入借款仓库，不同意还款就24小时后删除
        Db::startTrans();
        try {
            
            switch($data['status']){
                case '1':
                    //删除借款表中信息
                    $m_paper->where('id',$info1['id'])->delete();
                    //组装已完成借款表信息
                    unset($info1['id']);
                    unset($info1['status']);
                    unset($info1['expire_day']);
                    $info1['final_money']=$info['final_money'];
                    $info1['update_time']=$data['update_time'];
                    Db::name('paper_old')->insert($info1);
                    //还款完成恢复用户额度
                    Db::name('user')->where('id',$info1['borrower_id'])->setDec('money1',$info1['money']);
                    $data_action['action'].=',同时恢复了用户额度';
                     
                    break;
                 case '2':
                     
                    break; 
                default :throw new \Exception('信息错误');break;
           } 
           //更新还款状态
           $m->where($where)->update($data);
           Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('保存失败！'.$e->getMessage());
        }
       
        Db::name('action')->insert($data_action);
        $this->success('保存成功！',url('index'));
         
    }
    
}
