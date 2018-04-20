<?php
namespace app\stock\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminStockController extends AdminBaseController
{
    private $m;
    private $order;
    private $stock_status;
    public function _initialize()
    {
        parent::_initialize();
        $this->m            = Db::name('stock');
        $this->order        = 'id asc';
        $this->stock_status = config('stock_status');
        $this->assign('flag', '股票');

        $this->assign('stock_status', $this->stock_status);
    }

    /**
     * 股票列表
     * @adminMenu(
     *     'name'   => '股票列表',
     *     'parent' => 'stock/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '股票列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $m = $this->m;

        $data  = $this->request->param();
        $where = [];
        if (empty($data['status'])) {
            $data['status'] = 0;
        } else {
            $where['status'] = ['eq', $data['status']];
        }
        if (empty($data['code0'])) {
            $data['code0'] = '';
        } else {
            $where['code0'] = ['like', '%' . $data['code0'] . '%'];
        }
        $list = $m->where($where)->order($this->order)->paginate(10);

        // 获取分页显示
        $page = $list->appends($data)->render();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 股票编辑
     * @adminMenu(
     *     'name'   => '股票编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '股票编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m    = $this->m;
        $id   = $this->request->param('id');
        $info = $m->where('id', $id)->find();

        $this->assign('info', $info);

        //不同类别到不同的页面
        return $this->fetch();
    }
    /**
     * 股票编辑1
     * @adminMenu(
     *     'name'   => '股票编辑1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '股票编辑1',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $m    = $this->m;
        $data = $this->request->param();

        $info = $m->where('id', $data['id'])->find();
        if (empty($info)) {
            $this->error('数据错误');
        }
        $data['time'] = time();
        $row          = $m->where('id', $data['id'])->update($data);
        $data_action  = [
            'aid'    => session('ADMIN_ID'),
            'type'   => 'stock',
            'key'    => $info['code0'],
            'time'   => $data['time'],
            'ip'     => get_client_ip(),
            'action' => '对股票' . $info['code0'] . '更新',
        ];
        if ($row === 1) {
            Db::name('action')->insert($data_action);
            $this->success('修改成功', url('index'));
        } else {
            $this->error('修改失败');
        }

    }
    /**
     * 股票删除
     * @adminMenu(
     *     'name'   => '股票删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '股票删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $m  = $this->m;
        $id = $this->request->param('id', 0, 'intval');

        $row = $m->where(['id' => ['eq', $id]])->delete();
        if ($row === 1) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
        exit;
    }

    /**
     * 股票添加
     * @adminMenu(
     *     'name'   => '股票添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '股票添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {

        return $this->fetch();
    }

    /**
     * 股票添加1
     * @adminMenu(
     *     'name'   => '股票添加1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '股票添加1',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {

        $m    = $this->m;
        $data = $this->request->param();
        if (strlen($data['code']) != 6 || $data['code'] <= 1) {
            $this->error('股票代码错误');
        }
        $code1 = 'hs' . $data['code'];
        $code2 = 'sz' . $data['code'];
        if ($data['code0'] != $code1 && $data['code0'] != $code2) {
            $this->error('股票代码不一致');
        }
        $data['time'] = time();
        $data_action  = [
            'aid'    => session('ADMIN_ID'),
            'type'   => 'stock',
            'key'    => $data['code0'],
            'time'   => $data['time'],
            'ip'     => get_client_ip(),
            'action' => '添加股票' . $data['code0'],
        ];
        $row = $m->insertGetId($data);
        if ($row >= 1) {
            Db::name('action')->insert($data_action);
            $this->success('已成功添加', url('index'));
        } else {
            $this->error('添加失败');
        }
        exit;
    }

}