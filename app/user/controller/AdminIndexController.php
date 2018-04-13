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
        $where   = ['user_type'=>2];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['user_login|user_nickname|mobile']    = ['eq', $keyword];
        }
        $usersQuery = Db::name('user'); 
        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("create_time DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
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
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
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
        
        $info=Db::name("user")->where(["id" => $id, "user_type" => 2])->find();
        if(empty($info)){
            $this->error('用户不存在');
        }
        $tmp='pic/'.md5($info['user_login']);
        $info['pic1']=$tmp.'camera1.jpg';
        $info['pic2']=$tmp.'camera2.jpg';
        $info['pic3']=$tmp.'camera3.jpg';
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
        $user=$m_user->where(["id" => $data['id'], "user_type" => 2])->find();
        if(empty($user)){
            $this->error('无此用户');
        }
        if(preg_match(config('reg_money'), $data['money0'])!=1){
            $this->error('授信金额错误'.$data['money0']);
        }
        $data_action=[
            'aid'=>session('ADMIN_ID'),
            'type'=>'user',
            'time'=>time(),
            'ip'=>get_client_ip(),
            'action'=>'对用户'.$user['user_nickname'].'的操作:', 
        ];
        $action=0;
        if($user['is_name']!=$data['is_name']){
            $action=1;
            $data_action['action'].='更改实名认证'.$user['is_name'].'为'.$data['is_name'].'.';
        }
        if($user['money0']!=$data['money0']){
            $action=1;
            $data_action['action'].='修改额度'.$user['money0'].'为'.$data['money0'].'.'; 
        }
        if($action==0){ 
            $this->success('未修改',url('index'));
        }else{
            $m_user->where('id',$data['id'])->update($data);
            Db::name('action')->insert($data_action);
            $this->success('保存成功',url('index'));
        }
      
    }
}
