<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理组',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理组'
 * )
 */
class UserController extends AdminBaseController
{

    /**
     * 管理员列表
     * @adminMenu(
     *     'name'   => '管理员',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        //判断管理员权限
        $shop0=session('shop');
        if($shop0['id']==1){
            $where = ["u.user_type" => 1];
        }else{
            $where = ["u.user_type" => 1,'u.shop'=>$shop0['id']];
        }
        $data=$this->request->param();
        /**搜索条件**/
        if(empty($data['user_login'])){
            $data['user_login']='';
        }else{
            $where['u.user_login'] = ['like', '%'.$data['user_login'].'%'];
        } 
        if(empty($data['mobile'])){
            $data['mobile']='';
        }else{
            $where['u.mobile'] = ['like', '%'.$data['mobile'].'%'];
        }
        
        $users = Db::name('user')
            ->field('u.*,s.website,s.name,s.code')
            ->alias('u')
            ->join('cmf_shop s','s.id=u.shop')
            ->where($where)
            ->order("s.fid asc,s.id asc")
            ->paginate(10);
        // 获取分页显示
        $page = $users->appends($data)->render();

        $rolesSrc = Db::name('role')->select();
        $roles    = [];
        foreach ($rolesSrc as $r) {
            $roleId           = $r['id'];
            $roles["$roleId"] = $r;
        }
        $this->assign("data", $data);
        $this->assign("page", $page);
        $this->assign("roles", $roles);
        $this->assign("users", $users);
        $this->assign('website',config('website'));
        return $this->fetch();
    }

    /**
     * 管理员添加
     * @adminMenu(
     *     'name'   => '管理员添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        //不能添加被禁用的角色和超管角色
        $where=[
            'status'=>['eq',1],
            'id'=>['neq',1]
        ];
        //只能添加比自己权限小的角色 ，即list_order>=自己
        $aid=cmf_get_current_admin_id();
        
        if($aid!=1){
            $roles=Db::name('role_user')
            ->field('r.list_order')
            ->alias('ru')
            ->join('cmf_role r','ru.role_id=r.id')
            ->where('ru.user_id',$aid)
            ->order('r.list_order asc')
            ->find(); 
            $where['list_order']=['egt',$roles['list_order']];
        }
         
        $shop=session('shop');
        $m_shop=Db::name('shop');
        $where_shop=[];
        if($shop['id']!=1){
            $ids=$m_shop->where('fid',$shop['id'])->column('id');
            $ids[]=$shop['id'];
            $where_shop['id']=['in',$ids];
        }
        $shops=$m_shop->where($where_shop)->column('id,code,name');
        $roles = Db::name('role')->where($where)->order("list_order asc")->select();
        $this->assign("roles", $roles);
        $this->assign("shops", $shops);
        return $this->fetch();
    }

    /**
     * 管理员添加提交
     * @adminMenu(
     *     'name'   => '管理员添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        //添加验证添加角色权限小于自己
        $where=[
            'status'=>['eq',1],
            'id'=>['neq',1]
        ];
        $aid=cmf_get_current_admin_id();
        if($aid!=1){
            $roles=Db::name('role_user')
            ->field('r.list_order')
            ->alias('ru')
            ->join('cmf_role r','ru.role_id=r.id')
            ->where('ru.user_id',$aid)
            ->order('r.list_order asc')
            ->find();
            $where['list_order']=['egt',$roles['list_order']];
        }
        $roles = Db::name('role')->where($where)->column('id');
        $role_ids = $_POST['role_id'];
        //对比数组得到在$role_ids中却不在$roles中的值，如果有就错误了
        $result = array_diff($role_ids, $roles);
        if(!empty($result)){
            $this->error('数据错误');
        }
        unset($where);
        //原程序
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $result             = DB::name('user')->insertGetId($_POST);
                    if ($result !== false) {
                        //$role_user_model=M("RoleUser");
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        $this->success("添加成功！", url("user/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 管理员编辑
     * @adminMenu(
     *     'name'   => '管理员编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id    = $this->request->param('id', 0, 'intval');
        
        //不能添加被禁用的角色和超管角色
        $where=[
            'status'=>['eq',1],
            'id'=>['neq',1]
        ];
        //只能添加比自己权限小的角色 ，即list_order>=自己
        $aid=session('ADMIN_ID');
        
        if($aid!=1){
            $roles=Db::name('role_user')
            ->field('r.list_order')
            ->alias('ru')
            ->join('cmf_role r','ru.role_id=r.id')
            ->where('ru.user_id',$aid)
            ->order('r.list_order asc')
            ->find();
            $where['list_order']=['egt',$roles['list_order']];
        }
        $roles = DB::name('role')->where($where)->order("list_order asc")->select();
        $this->assign("roles", $roles);
        $role_ids = DB::name('RoleUser')->where(["user_id" => $id])->column("role_id");
        $this->assign("role_ids", $role_ids);

        $user = DB::name('user')->where(["id" => $id])->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 管理员编辑提交
     * @adminMenu(
     *     'name'   => '管理员编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        //添加验证添加角色权限小于自己
        $where=[
            'status'=>['eq',1],
            'id'=>['neq',1]
        ];
        $aid=cmf_get_current_admin_id();
        if($aid!=1){
            $roles=Db::name('role_user')
            ->field('r.list_order')
            ->alias('ru')
            ->join('cmf_role r','ru.role_id=r.id')
            ->where('ru.user_id',$aid)
            ->order('r.list_order asc')
            ->find();
            $where['list_order']=['egt',$roles['list_order']];
        }
        $roles = Db::name('role')->where($where)->column('id');
        $role_ids =  $this->request->param('role_id/a');
        if(empty($role_ids)){
            $this->error("请为此用户指定角色！");
        }
        //对比数组得到在$role_ids中却不在$roles中的值，如果有就错误了
        $result = array_diff($role_ids, $roles);
        if(!empty($result)){
            $this->error('数据错误');
        }
        unset($where);
        //源程序
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                if (empty($_POST['user_pass'])) {
                    unset($_POST['user_pass']);
                } else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                }
                $role_ids = $this->request->param('role_id/a');
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User.edit');

                if ($result !== true) {
                    // 验证失败 输出错误信息
                    $this->error($result);
                } else {
                    $result = DB::name('user')->update($_POST);
                    if ($result !== false) {
                        $uid = $this->request->param('id', 0, 'intval');
                        DB::name("RoleUser")->where(["user_id" => $uid])->delete();
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            DB::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
                        }
                        $this->success("保存成功！", url("user/index"));
                    } else {
                        $this->error("保存失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 管理员个人信息修改
     * @adminMenu(
     *     'name'   => '个人信息',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员个人信息修改',
     *     'param'  => ''
     * )
     */
    public function userInfo()
    {
        $id   = cmf_get_current_admin_id();
        $user = Db::name('user')->where(["id" => $id])->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 管理员个人信息修改提交
     * @adminMenu(
     *     'name'   => '管理员个人信息修改提交',
     *     'parent' => 'admin/User/userInfo',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员个人信息修改提交',
     *     'param'  => ''
     * )
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data             = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id']       = cmf_get_current_admin_id();
            $create_result    = Db::name('user')->update($data);;
            if ($create_result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * 管理员删除
     * @adminMenu(
     *     'name'   => '管理员删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id == 1) {
            $this->error("最高管理员不能删除！");
        }

        if (Db::name('user')->delete($id) !== false) {
            Db::name("RoleUser")->where(["user_id" => $id])->delete();
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 停用管理员
     * @adminMenu(
     *     'name'   => '停用管理员',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '停用管理员',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
            if ($result !== false) {
                $this->success("管理员停用成功！", url("user/index"));
            } else {
                $this->error('管理员停用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 启用管理员
     * @adminMenu(
     *     'name'   => '启用管理员',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '启用管理员',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '1');
            if ($result !== false) {
                $this->success("管理员启用成功！", url("user/index"));
            } else {
                $this->error('管理员启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }
}