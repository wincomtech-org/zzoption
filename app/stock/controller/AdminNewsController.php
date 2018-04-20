<?php
namespace app\stock\controller;

use app\stock\model\StockNewsModel;
use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminNewsController
 * @package app\stock\controller
 * @adminMenuRoot(
 *     'name'   => '新闻管理',
 *     'action' => 'default',
 *     'parent' => 'stock/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   => '',
 *     'remark' => '新闻管理'
 * )
 */
class AdminNewsController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        // $this->mq      = Db::name('stock_news');
        $this->scModel = new StockNewsModel;
    }

    /**
     * @adminMenu(
     *     'name'   => '新闻列表',
     *     'parent' => 'stock/AdminNews/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '新闻列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $param = $this->request->param();

        $where   = [];
        $keyword = isset($param['keyword']) ? $param['keyword'] : '';
        if (!empty($keyword)) {
            $where['title'] = ['like', '%' . $keyword . '%'];
        }

        $list = $this->scModel->alias('a')
            ->field('a.id,title,source,create_time,a.list_order,b.name')
            ->join('stock_news_category b', 'a.cate_id=b.id','LEFT')
            ->where($where)
            ->order('list_order,create_time DESC')
            ->paginate(15);

        $this->assign('keyword', $keyword);
        $this->assign('list', $list->items());
        $this->assign('pager', $list->appends($param)->render());
        return $this->fetch();
    }
    public function add()
    {
        $cateTree = $this->scModel->cateTree();
        $this->assign('categories_tree', $cateTree);
        return $this->fetch();
    }
    public function addPost()
    {
        $data         = $this->request->param();
        $data['shop'] = cmf_get_current_admin_id();

        // $result = $this->mq->insert($data);
        $result = $this->scModel->isUpdate(false)->allowField(true)->save($data);

        if ($result === false) {
            $this->error('添加失败!');
        }
        $this->success('添加成功!', url('AdminNews/index'));
    }

    // 编辑
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        // $post = $this->mq->where('id',$id)->find();
        $post = $this->scModel->get($id);
        if (empty($post)) {
            $this->error('数据丢失！');
        } else {
            $post = $post->toArray();
        }

        $cateTree = $this->scModel->cateTree($post['cate_id']);
        $this->assign('categories_tree', $cateTree);
        $this->assign($post);
        return $this->fetch();
    }
    public function editPost()
    {
        $data = $this->request->param();
        if (!isset($data['shop'])) {
            $data['shop'] = cmf_get_current_admin_id();
        }

        // $result = $this->mq->update($data);
        $result = $this->scModel->isUpdate(true)->allowField(true)->save($data);

        if ($result === false) {
            $this->error('保存失败!');
        }
        $this->success('保存成功!');
    }

    public function listOrder()
    {
        parent::listOrders(Db::name('stock_news'));
        $this->success('排序更新成功！', '');
    }

    public function delete()
    {
        // $this->scModel->destroy($data);
    }
}
