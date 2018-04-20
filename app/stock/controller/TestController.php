<?php
namespace app\stock\controller;

use cmf\controller\HomeBaseController;
use app\stock\model\StockModel;
use app\stock\model\StockIndiceModel;
use app\stock\model\StockNewsModel;
use calendar\Calendar;
use think\Db;
use sms\Dy;

/**
* 股票代码测试
* \app\stock\controller\TestController - 2.php
*/
class TestController extends HomeBaseController
{
    public function sms()
    {
        $sms = new Dy();
        // $result = $sms->dySms('18715511536');

        // $result = Dy::oneSms();

        dump($result);
    }

    public function test()
    {
        $scModel = new StockModel;
        // $data = $scModel->getStockBase('s_sh000001');
        // $data = $scModel->getIndice('s_sh000001');
        // $data = $scModel->getPrice();
        // $dtime = strtotime('-7 day');
        // $dtime = time();
        // $dnum = $scModel->where('time','lt',$dtime)->delete();

        // lothar_nonTradingDay('2018',1);
        // dump($dnum);
        // dump($data);
        exit('ok');
    }
    public function index()
    {
        $code = $this->request->param('code', 's_sh600000');

        $scModel = new StockModel;
        $result = $scModel->getIndice($code);
        $result = $scModel->getPrice($code);

        dump($result);
    }
    public function index2()
    {
        $code = $this->request->param('code', 'sh000001');

        $scModel = new StockModel;
        $result = $scModel->getStockBase($code);

        $data = [
            'name'  => $result[1],
            'price' => round($result[3], 2),
        ];
        dump($data);
    }

    public function stock()
    {
        // $m = Db::name('stock_indice');
        $scModel = new StockModel;

        $codes = $scModel->limit(700)->column('code0');
        // dump($codes);
        
        $code = '';
        foreach ($codes as $val) {
            $code .= 's_'.$val.',';
        }
        $code = substr($code,0,-1);
        // $code = implode(',',$codes);
        
        // dump($code);

        $data = $scModel->getIndice($code);
        // $data = $scModel->getPrice($code);
        dump($data);

        // $m->insertAll($post);
        // model('StockIndice')->isUpdate(true)->saveAll($post);
        exit('股市指数获取结束');
    }

    public function stockList()
    {
        Db::execute("TRUNCATE cmf_stock");
        $m           = Db::name('stock');
        $data0       = $m->column('id,name','code0');
        $c = count($data0);
        dump($c);
        dump($data0);
        die;
    }

    public function calendar()
    {
        $util   = new Calendar();
        $years  = 2018; //年份
        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]; //月份数组

        // 获取非工作日
        $unwork = lothar_nonTradingDay('2018',1);
        $unwork = $unwork[2018];
        // 获取指定月份的
        // $cwk = [];
        // foreach ($unwork as $key => $tt) {
        //     if ($month==substr($key,0,2)) {
        //         $cwk[intval(substr($key, -2))] = $tt;
        //         // $cwk[date('j','2018'.$key)] = $tt;
        //     }
        // }

        $post = [];
        foreach ($months as $m) {
            $post = array_merge($post,$util->getWork($years,$m,$unwork));
        }
        // dump($post);

        // $result = Db::name('stock_calendar')->insertAll($post);
        // dump($result);
    }

}