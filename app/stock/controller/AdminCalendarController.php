<?php
namespace app\stock\controller;

use calendar\Calendar;
use cmf\controller\AdminBaseController;

/**
 * Class AdminCalendarController
 * @package app\stock\controller
 * @adminMenuRoot(
 *     'name'   => '日历管理',
 *     'action' => 'default',
 *     'parent' => 'stock/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 200,
 *     'icon'   => '',
 *     'remark' => '日历管理'
 * )
 */
class AdminCalendarController extends AdminBaseController
{
    /**
     * @adminMenu(
     *     'name'   => '日历列表',
     *     'parent' => 'stock/AdminCalendar/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 100,
     *     'icon'   => '',
     *     'remark' => '日历列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        // $param = $this->request->param();

        // $where   = [];
        // $keyword = isset($param['keyword']) ? $param['keyword'] : '';
        // if (!empty($keyword)) {
        //     $where['title'] = ['like', '%' . $keyword . '%'];
        // }

        // $list = $this->scModel->alias('a')
        //     ->field('a.id,title,source,create_time,a.list_order,b.name')
        //     ->join('stock_news_category b', 'a.cate_id=b.id')
        //     ->where($where)
        //     ->order('list_order,create_time DESC')
        //     ->paginate(15);

        // $this->assign('keyword', $keyword);
        // $this->assign('list', $list->items());
        // $this->assign('pager', $list->appends($param)->render());
        // return $this->fetch();
    }

    /**
     * [calendar description]
     * Calendar 函数
     * @return [type] [description]
     */
    public function calendar()
    {
        $year = $this->request->param('ddlYear',date('Y'),'intval');
        $month = $this->request->param('ddlMonth',date('n'),'intval');

        $util   = new Calendar();
        $years  = array(2018, 2019); //年份选择自定义
        $months = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12); //月份数组

        $calendar = $util->threshold($year, $month); //获取各个边界值
        $caculate = $util->caculate($calendar); //计算日历的天数与样式
        $draws    = $util->draw($caculate); //画表格，设置table中的tr与td

        $this->assign('year',$year);
        $this->assign('years',$years);
        $this->assign('month',$month);
        $this->assign('months',$months);
        $this->assign('draws',$draws);

        return $this->fetch();
    }
}
