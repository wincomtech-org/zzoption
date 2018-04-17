<?php
namespace app\stock\controller;

use cmf\controller\HomeBaseController;
use think\Db;
use app\stock\model\StockModel;
use app\stock\model\StockIndiceModel;
use app\stock\model\StockNewsModel;

/**
 * 股票数据
 * \app\stock\controller\IndexController - 2.php
 */
class IndexController extends HomeBaseController
{
    public function test()
    {
        // $scModel = new StockModel;
        // $data = $scModel->getStockBase('s_sh000001');
        // $data = $scModel->getIndice('s_sh000001');
        // $data = $scModel->getPrice('s_sh000001');

        // lothar_nonTradingDay('2018');

        $data = '{"2018":{"0101":"2","0215":"1","0216":"2","0217":"2","0218":"2","0219":"1","0220":"1","0221":"1","0405":"2","0406":"1","0407":"1","0429":"1","0430":"1","0501":"2","0616":"1","0617":"1","0618":"2","0922":"1","0923":"1","0924":"2","1001":"2","1002":"2","1003":"2","1004":"1","1005":"1","1006":"1","1007":"1"}}';
        $arr = json_decode($data, true);
        dump($arr);

        // dump($data);
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
        $m = Db::name('stock_indice');
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


}