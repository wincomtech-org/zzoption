<?php
namespace app\stock\model;

use think\Model;

/**
 * 股票数据模型
 * https://zhidao.baidu.com/question/166686795.html
 * K线图
 * 不同数字开头代表不同票种，返回数据可能不一样
 */
class StockModel extends Model
{
    /**
     * 获取股票实时价格
     * Sina股票数据接口
     * 接口：
        http://hq.sinajs.cn/list=sh601003,sh601001
        http://hq.sinajs.cn/list=sh601006
     * 这个url会返回一串文本，例如：
        var hq_str_sh601006="大秦铁路, 27.55, 27.25, 26.91, 27.55, 26.20, 26.91, 26.92, 22114263, 589824680, 4695, 26.91, 57590, 26.90, 14700, 26.89, 14300, 26.88, 15100, 26.87, 3100, 26.92, 8900, 26.93, 14230, 26.94, 25150, 26.95, 15220, 26.96, 2008-01-11, 15:05:32";
        这个字符串由许多数据拼接在一起，不同含义的数据用逗号隔开了，按照程序员的思路，顺序号从0开始。
            0：”大秦铁路”，股票名字；
            1：”27.55″，今日开盘价；
            2：”27.25″，昨日收盘价；
            3：”26.91″，当前价格；
            4：”27.55″，今日最高价；
            5：”26.20″，今日最低价；
            6：”26.91″，竞买价，即“买一”报价；
            7：”26.92″，竞卖价，即“卖一”报价；
            8：”22114263″，成交的股票数，由于股票交易以一百股为基本单位，所以在使用时，通常把该值除以一百；
            9：”589824680″，成交金额，单位为“元”，为了一目了然，通常以“万元”为成交金额的单位，所以通常把该值除以一万；
            10：”4695″，“买一”申请4695股，即47手；
            11：”26.91″，“买一”报价；
            12：”57590″，“买二”
            13：”26.90″，“买二”
            14：”14700″，“买三”
            15：”26.89″，“买三”
            16：”14300″，“买四”
            17：”26.88″，“买四”
            18：”15100″，“买五”
            19：”26.87″，“买五”
            20：”3100″，“卖一”申报3100股，即31手；
            21：”26.92″，“卖一”报价
            (22, 23), (24, 25), (26,27), (28, 29)分别为“卖二”至“卖四的情况”
            30：”2008-01-11″，日期；
            31：”15:05:32″，时间；
     */
    
    /*
     * 获取创业板、深圳、上海股票大盘指数
     * 大盘 - S内盘指数
     * 接口：http://hq.sinajs.cn/list=
     * 参数含义
        00开头：
            var hq_str_s_sh000001="上证指数,3094.668,-128.073,-3.97,436653,5458126";
            数据含义分别为：0指数名称，1当前点数、2当前价格、3涨跌率、4成交量（手）、5成交额（万元）
        600开头的：
            var hq_str_s_sh600000="浦发银行,11.770,0.270,2.35,287482,33608";
            参数含义：0指数名称、1当前价格、2涨跌、3涨跌率、4成交量、5成交额
            var hq_str_sh600000="浦发银行,11.520,11.500,11.770,11.790,11.510,11.760,11.770,28748298,336076808.000,33500,11.760,98500,11.750,96000,11.740,131500,11.730,58700,11.720,10300,11.770,127378,11.780,317400,11.790,731525,11.800,229550,11.810,2018-04-10,15:00:00,00";

     * 获取单个
        string(60) "var hq_str_s_sh000001="上证指数,3190.3216,0.0000,0.00,0,0";
        "
     * 获取多个
        string(168) "var hq_str_s_sh000001="上证指数,3190.3216,0.0000,0.00,0,0";
        var hq_str_s_sz399001="深证成指,0.00,0.000,0.00,0,0";
        var hq_str_s_sz399006="创业板指,0.00,0.000,0.00,0,0";
        "
     */

    /**
     * 获取股票数据基础代码
     * @param  string $code [股票代码]
     * @param  string $type [股票类型]
     * @return [type]        [description]
     */
    public function getStockBase($code)
    {
        // $code = 's_sh000001';
        // $code = 's_sh000001,s_sz399001,s_sz399006';
        $url = 'http://hq.sinajs.cn/list=' . $code;

        $content = cmf_curl_get($url);
        $content = iconv('GBK', 'UTF-8//IGNORE', $content);
        $content = cmf_strip_chars($content,"\r\n");
        $content = trim($content,';');
        // dump($content);die;
        $data    = explode(';', $content);
        // dump($data);die;
        $pattern = '/(?<==").*?(?=")/ism';
        $post = [];
        if (!empty($data)) {
            $codes   = explode(',', $code);
            foreach ($data as $key => $val) {
                preg_match($pattern, $val, $arr);
                $tmp    = explode(',', $arr[0]);
                // $tmpNums= array_push($tmp,$codes[$key]);
                $post[] = array_merge([$codes[$key]],$tmp);
            }
        }
        // dump($post);die;

        // $m->insertAll($post);
        // model('StockIndice')->isUpdate(true)->saveAll($post);

        return $post;
    }

    /**
     * 获取大盘指数 
     * s_  [S内盘指数]
     * s_sh000001
     * @param  string $code [description]
     * @return [type]       [description]
     */
    public function getIndice($code='s_sh000001')
    {
        $data = $this->getStockBase($code);
        foreach ($data as $key => $row) {
            $tmp[] = [
                'id'      => $key + 1,
                // 'name'    => $row[1],
                'count'   => round($row[2], 2),
                'num'     => round($row[3], 2),
                'percent' => round($row[4], 2),
            ];
        }

        return $tmp;
    }

    /**
     * 获取股票价格
     * s_  []
     * s_sh000001  sh000001
     * @param  string $code [description]
     * @return [type]       [description]
     */
    public function getPrice($code='s_sh000001')
    {
        $data = $this->getStockBase($code);
        foreach ($data as $row) {
            $tmp[] = [
                'name'    => $row[1],
                'price'   => round($row[2], 2),
                'percent' => round($row[4], 2),
            ];
        }

        return $tmp;
    }
}