<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用户管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用户管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用户组',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用户组'
 * )
 */
class AdminIndexController extends AdminBaseController
{

    /**
     * 后台本站用户列表
     * @adminMenu(
     *     'name'   => '本站用户',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $where   = ["u.user_type" => 2];
        $request = $this->request->param();
        //判断管理员权限
        $shop0=session('shop');
        if($shop0['id']!=1){
            $where['u.shop']=$shop0['id'];
        } 
        if (empty($request['uid'])) {
            $request['uid']=''; 
        }else{
            $where['u.id'] = intval($request['uid']);
        }
        if (empty($request['code'])) {
            $request['code']='';
        }else{
            $where['s.code'] = intval($request['code']);
        }
        $keywordComplex = [];
        if (empty($request['keyword'])) {
            $request['keyword']='';
        }else{
            $keyword = $request['keyword'];
            $keywordComplex['u.user_login|u.user_nickname|u.mobile']    = ['eq', $keyword];
        }
        
        
        $list= Db::name('user')
        ->field('u.*,s.website,s.name,s.code')
        ->alias('u')
        ->join('cmf_shop s','s.id=u.shop')
        ->whereOr($keywordComplex)
        ->where($where)
        ->order("u.create_time desc")
        ->paginate(10);
        // 获取分页显示
        $page = $list->appends($request)->render();
        $this->assign('data', $request);
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 本站用户拉黑
     * @adminMenu(
     *     'name'   => '本站用户拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $this->success("会员拉黑成功！");
            } else {
                $this->error('会员拉黑失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id , "user_type" => 2])->setField('user_status', 1);
            $this->success("会员启用成功！");
        } else {
            $this->error('数据传入失败！');
        }
    }
    
    /**
     * 用户详情
     * @adminMenu(
     *     'name'   => '用户详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '用户详情',
     *     'param'  => ''
     * )
     */
    public function info()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        $info=Db::name("user")->where(["id" => $id ])->find();
        if(empty($info)){
            $this->error('用户不存在');
        }
        
        $info['more']=json_decode($info['more'],true);
        
        $this->assign('info',$info);
        return $this->fetch();
    }
    /**
     * 用户详情执行
     * @adminMenu(
     *     'name'   => '用户详情执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '用户详情执行',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data = $this->request->param('','trim');
        $m_user=Db::name("user");
        $user=$m_user->where(["id" => $data['id'] ])->find();
        if(empty($user)){
            $this->error('无此用户');
        }
         
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'user',
            'key'=>$user['id'],
            'time'=>time(),
            'ip'=>get_client_ip(),
            'action'=>'对用户'.$user['user_nickname'].'的操作:', 
        ];
        $action=0;
        $data_user=[];
        $m_user->startTrans();
        if($user['is_name']!=$data['is_name']){
            $action=1;
            $data_user['is_name']=$data['is_name'];
            $data_action['action'].='更改实名认证'.$user['is_name'].'为'.$data['is_name'].'.';
        }
        if(!empty($data['add_money'])){
            $action=1;
            $data['add_money']=round($data['add_money'],2);
            //2充值，3提现
           
            if($data['add_money']>0){
                $money_type=2;
                $money_dsc='手动充值';
                $money_abs=$data['add_money'];
            }else{
                $money_type=3;
                $money_dsc='手动提现';
                $money_abs=abs($data['add_money']);
            }
            $dsc='管理员'.$money_dsc.$money_abs.'元.';
            $data_action['action'].=$dsc;
            $data_user['money']=bcadd($user['money'],$data['add_money'],2);
            //记录资金明细
            $data_money=[
                'uid'=>$user['id'],
                'money'=>$data['add_money'],
                'status'=>1,
                'type'=>$money_type,
                'time'=>$data_action['time'],
                'insert_time'=>$data_action['time'],
                'dsc'=>$dsc,
            ];
            Db::name('money')->insert($data_money);
            $data_msg=[
                'aid'=>$data_action['aid'],
                'title'=>'管理员'.$money_dsc,
                'content'=>$dsc,
                'type'=>2,
                'uid'=>$user['id'],
                'mobile'=>$user['mobile'],
                'uname'=>$user['user_nickname'],
                'sms'=>'money'
            ];
            zz_msg($data_msg);
        }
        
        if($action==0){ 
            $m_user->commit();
            $this->success('未修改',url('index'));
        }else{
            $m_user->where('id',$data['id'])->update($data_user);
            Db::name('action')->insert($data_action);
            $m_user->commit();
            $this->success('保存成功',url('index'));
        }
      
    }
}
