<?php
namespace app\stock\controller;

use app\stock\model\StockModel;
use calendar\Calendar;
use cmf\controller\HomeBaseController;
use sms\Dy;
use stock\Creeper;
use stock\Stock;
use think\Db;
use wx\Wx;

/**
 * 股票代码测试
 * \app\stock\controller\TestController - 2.php
 */
class TestController extends HomeBaseController
{
    public function sms()
    {
        // session('sms',null);
        // $msg = session('sms');
        // // $msg = [];
        // dump($msg);
        // dump($msg['time']);die;
        // dump(intval(session('sms.time')));die;
        $result = '';
        $sms = new Dy();

        $tp = [
            'name' => 'lothar',
            'indent' => '订单',
        ];
        $result = $sms->dySms('','order',$tp);
        // $result = $sms->batchSms();

        // $result = Dy::oneSms();

        dump($result);
    }
    public function auth()
    {
        import('stock.Verify');

        // $params['bankcard'] = '银行卡号码';
        $params['realName'] = '';
        $params['Mobile']   = '';
        $params['cardNo']   = '';

        //发送远程请求;
        $result = bankcardVerify($params);
        var_dump("<pre>");
        var_dump($result);
        //返回结果
        if ($result['error_code'] == 0) {
            echo $result['reason']; //信息一致
        } else {
            echo $result['reason']; //信息不一致
        }
        exit();
    }

    public function test()
    {
        $scModel = new Stock;
        $data = [];
        // $data = $scModel->getStockBase('s_sh000001');
        // $data = $scModel->getIndice('s_sh000001');
        // $data = $scModel->getPrice();
        // $data = $scModel->nowapi_call();
        // dump($data);

        // $stockModel = new StockModel;
        // $dtime = strtotime('-7 day');
        // $dtime = time();
        // $dnum = $stockModel->where('time','lt',$dtime)->delete();
        // dump($dnum);

        // lothar_nonTradingDay('2018',1);

        // $cModel = new Creeper;
        // dump($cModel->creeper());
        exit('ok');
    }
    public function index()
    {
        $code = $this->request->param('code', 's_sh600000');

        $scModel = new Stock;
        $result  = $scModel->getIndice($code);
        $result  = $scModel->getPrice($code);

        dump($result);
    }
    public function index2()
    {
        $code = $this->request->param('code', 'sh000001');

        $scModel = new Stock;
        $result  = $scModel->getStockBase($code);

        $data = [
            'name'  => $result[1],
            'price' => round($result[3], 2),
        ];
        dump($data);
    }

    public function stock()
    {
        // $m = Db::name('stock_indice');
        $scModel    = new StockModel;
        $stockModel = new Stock;

        $codes = $scModel->limit(700)->column('code0');
        // dump($codes);

        $code = '';
        foreach ($codes as $val) {
            $code .= 's_' . $val . ',';
        }
        $code = substr($code, 0, -1);
        // $code = implode(',',$codes);

        // dump($code);

        $data = [];
        // $data = $stockModel->getIndice($code);
        // $data = $stockModel->getPrice($code);
        dump($data);

        // $m->insertAll($post);
        // model('StockIndice')->isUpdate(true)->saveAll($post);
        exit('股市指数获取结束');
    }

    public function stockList()
    {
        // Db::execute("TRUNCATE cmf_stock");
        $m     = Db::name('stock');
        $data0 = $m->column('id,name', 'code0');
        $c     = count($data0);
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
        $unwork = lothar_nonTradingDay('2018', 1);
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
            $post = array_merge($post, $util->getWork($years, $m, $unwork));
        }
        // dump($post);

        // $result = Db::name('stock_calendar')->insertAll($post);
        // dump($result);
    }
}
