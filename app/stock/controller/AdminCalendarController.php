<?php
namespace app\stock\controller;

use calendar\Calendar;
use cmf\controller\AdminBaseController;
use think\Db;

class AdminCalendarController extends AdminBaseController
{
    /**
     * @adminMenu(
     *     'name'   => '日历管理',
     *     'parent' => 'stock/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '日历管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $year  = $this->request->param('ddlYear', date('Y'), 'intval');
        $month = $this->request->param('ddlMonth', date('n'), 'intval');
        // $day   = date('j');

        $util   = new Calendar();
        $years  = array(2018); //年份选择自定义
        $months = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12); //月份数组

        $start         = mktime(0, 0, 0, $month, 1, $year);
        $end           = strtotime('+1 month -1 day', $start);
        $where['time'] = [['>= time', $start], ['<= time', $end]];
        // 'id,type,is_trade,time'
        $cals  = Db::name('stock_calendar')->where($where)->select()->toArray();
        $draws = $util->draws($cals);

        $this->assign('year', $year);
        $this->assign('years', $years);
        $this->assign('month', $month);
        $this->assign('months', $months);
        $this->assign('today', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        $this->assign('draws', $draws);

        return $this->fetch();
    }

    public function calendar()
    {
        $id = $this->request->param('id/d', 0, 'intval');

        $calen = Db::name('stock_calendar')->where('id', $id)->find();

        $this->assign($calen);
        return $this->fetch();
    }

    public function calendarPost()
    {
        $data = $this->request->param();
        $id   = $this->request->param('id/d', 0, 'intval');

        $result = Db::name('stock_calendar')->where('id', $id)->update($data);
        if ($result) {
            $this->success('更新成功');
        }
        $this->error('更新失败 或 数据无变化');
    }
}
