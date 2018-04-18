<?php
namespace app\stock\controller;

use calendar\Calendar;
use cmf\controller\AdminBaseController;
use think\Db;

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
        // $date = mktime(0, 0, 0, 4, 17, 2018);
        // $week = date("N", $date);
        // // $week = date("N");
        // echo $date;
        // echo "<br>";
        // dump($week);
        // echo strtotime('20180401').'<br>';
        // echo date('Y-m-d',1522512000).'<br>';
        // die;

        $year  = $this->request->param('ddlYear', date('Y'), 'intval');
        $month = $this->request->param('ddlMonth', date('n'), 'intval');

        $util   = new Calendar();
        $years  = array(2018, 2019, 2020); //年份选择自定义
        $months = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12); //月份数组

        // 获取非工作日
        $unwork = lothar_nonTradingDay('2018',1);
        $unwork = $unwork[2018];
        // 获取指定月份的
        $cwk = [];
        foreach ($unwork as $key => $tt) {
            if ($month==substr($key,0,2)) {
                $cwk[intval(substr($key, -2))] = $tt;
                // $cwk[date('j','2018'.$key)] = $tt;
            }
        }

        $calendar = $util->threshold($year, $month); //获取各个边界值
        $startTime = $calendar['firstDay'];
        $endTime = $calendar['lastDay'];
        $where['time'] = [['>= time', $startTime], ['<= time', $endTime]];
        // 'id,type,is_trade,time'
        $cals = Db::name('stock_calendar')->where($where)->select()->toArray();
        // dump($cals);die;
        $cws = [];
        foreach ($cals as $key => $val) {
            $cws[date('j',$val['time'])] = [
                'id'    => $val['id'],
                'type'  => $val['type'],
                'is_trade'=>$val['is_trade'],
            ];
        }

        $caculate = $util->caculate($calendar,$cwk,$cws); //计算日历的天数与样式
        $draws    = $util->draws($caculate); //画表格，设置table中的tr与td

        $this->assign('year', $year);
        $this->assign('years', $years);
        $this->assign('month', $month);
        $this->assign('months', $months);
        $this->assign('draws', $draws);

        return $this->fetch();
    }

    public function calendarDialog()
    {
        $dd   = $this->request->param();
        $date   = $this->request->param('date', '');
        $type   = $this->request->param('type', '');
        $calenId = $this->request->param('calenId', '');

        $time = strtotime($date);
        $is_trade = Db::name('stock_calendar')->where('time',$time)->value('is_trade');
        // $is_trade = empty($is_trade) ? (empty($type)?0:1) : 1;

        $this->assign('date', date('Y-m-d',$time));
        $this->assign('type', $type);
        $this->assign('calenId', $calenId);
        $this->assign('is_trade', $is_trade);
        return $this->fetch();
    }

    public function calendarDialogPost()
    {
        $data = $this->request->param();
        $id   = intval($data['calenId']);
        $post = [
            'type'      => $data['type'],
            'is_trade'  => $data['is_trade'],
            'time'      => strtotime($data['date'])
        ];
        // dump($post);die;

        if (empty($id)) {
            $result = Db::name('stock_calendar')->insert($post);
        } else {
            $result = Db::name('stock_calendar')->where('id',$id)->update($post);
        }
        if ($result) {
            $this->success('更新成功');
        }
        $this->error('更新失败 或 数据无变化');
    }
}