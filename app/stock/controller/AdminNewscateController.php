<?php
namespace app\stock\controller;

use cmf\controller\AdminBaseController;
use think\Db;

/**
 *  新闻分类
 */
class AdminNewscateController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->mq = Db::name('stock_news_category');
    }

    /**
     * 分类管理
     * @adminMenu(
     *     'name'   => '新闻分类',
     *     'parent' => 'stock/AdminNews/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '新闻分类',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $list = $this->mq->paginate(15);

        $this->assign('list', $list);
        $this->assign('pager', $list->render());
        return $this->fetch();
    }

     
    /**
     * 分类管理
     * @adminMenu(
     *     'name'   => '新闻分类编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '新闻分类编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id   = $this->request->param('id', 0, 'intval');
        $post = $this->mq->where('id', $id)->find();
        $this->assign($post);
        return $this->fetch();
    }
    /**
     * 分类管理
     * @adminMenu(
     *     'name'   => '新闻分类编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data = $this->request->param();

        $result = $this->mq->update($data);

        if ($result === false) {
            $this->error('保存失败!');
        }
        $this->success('保存成功!');
    }

    /**
     * @adminMenu(
     *     'name'   => '新闻分类排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        parent::listOrders($this->mq);
        $this->success("排序更新成功！", '');
    }
 
}
