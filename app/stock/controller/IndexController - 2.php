<?php
namespace app\stock\controller;

use cmf\controller\HomeBaseController;
use think\Db;
use app\stock\model\StockModel;
use app\stock\model\StockIndiceModel;
use app\stock\model\StockNewsModel;

/**
 * 股票数据
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

    /*获取指定网站新闻*/
    public function news()
    {
        // 证券时报网
        $source = ['type'=>1,'name'=>'证券时报网'];
        $url = 'http://stock.stcn.com/dapan/index.shtml'; //大盘
        // $url = [
        //     1=>'http://stock.stcn.com/dapan/index.shtml', //大盘
        //     2=>'http://stock.stcn.com/bankuai/index.shtml', //板块个股
        //     3=>'http://stock.stcn.com/xingu/index.shtml', //新股动态
        // ];
        // 原始HTML：<p class="tit"><a href="http://stock.stcn.com/2018/0413/14110373.shtml" target="_blank" title="年末沪指看高至4000点 人工智能最被看好">年末沪指看高至4000点 人工智能最被看好</a><span>[2018-04-13 08:25]</span></p>
        // [2018-04-13 08:25]
        /*
         * 匹配规则
         */
        $pattern = [
            "/<div class=\"mainlist\".*?>.*?<\/div>/ism",
            "/<li.*?>.*?<\/li>/ism",
            "/<p class=\"tit\"><a href=\"(.*?)\".*?>(.*?)<\/a><span>(.*?)<\/span><\/p>/ism",
            "/<div class=\"kx_left\">.*?<div class=\"intal_tit\">.*?<h2>(.*?)<\/h2>.*?<div class=\"info\">(.*?)<\/div>.*?<\/div>.*?<div class=\"txt_con\" id=\"ctrlfscont\">(.*?)<\/div>.*?<\/div>/ism",
            "\[\]"
        ];

        // 新浪财经的  暂时只能匹配博客的内容
        $source = ['type'=>2,'name'=>'新浪财经'];
        $url = 'http://finance.sina.com.cn/column/ggdp.shtml'; //[博客]个股点评
        // $url = [
        //     1=>'http://roll.finance.sina.com.cn/finance/zq1/gsjsy/index.shtml', //大盘评述
        //     2=>'http://roll.finance.sina.com.cn/blog/blogarticle/cj-bkgg/index.shtml', //板块个股
        //     4=>'http://finance.sina.com.cn/column/ggdp.shtml', //[博客]个股点评
        // ];
        // 原始HTML：<li><a href="http://blog.sina.com.cn/s/blog_b0b8d3ab0102xhyo.html?tj=fina" target="_blank">[博客]周四两市涨停个股分析（图）</a><span>(2018-04-12 16:43:39)</span></li>
        // (2018-04-13 09:37:19)
        /*
         * 匹配规则
         * 0 => '/<div class="listBlk">.*?<div class="hs01"><\/div>.*?<div class="hs01"><\/div>.*?<\/div>/ism',
         * 3 => '/id="articlebody".*?<div.*?id="sina_keyword_ad_area2".*?>(.*?)<div.*?class="shareUp">/ism',
         * 3 => '/id="sina_keyword_ad_area2".*?>()()(.*?)<\/div>.*?<div.*? class="shareUp"/ism',
         */
        $pattern = [
            '/<div class="hs01">.*?<\/div>.*?<\/div>/ism',
            '/<li>.*?<\/li>/ism',
            '/<a href="(.*?)".*?>(.*?)<\/a><span>(.*?)<\/span>/ism',
            '/<h1.*?>(.*?)<\/h1>.*?<div class="artinfo">.*?<span class="time">(.*?)<\/span><a.*?id="sina_keyword_ad_area2">(.*?)<div class="into_bloger">/ism',
            '\(\)',
        ];



        $scModel = new StockNewsModel;
        // 先清空数据，再重新写入
        // Db::query('TRUNCATE cmf_stock_news;');
        $result = 0;
        // 从数据库中获取最近时间戳
        $maxTime = $scModel->where('type',$source['type'])->max('create_time');
        $time = ['time'=>$maxTime];
        if (is_array($url)) {
            foreach ($url as $k => $v) {
                $result += $scModel->creeper($v,$pattern,$source,$k,$time);
            }
        } else {
            $result = $scModel->creeper($url,$pattern,$source,1,$time);
        }

        dump($result);
    }
}