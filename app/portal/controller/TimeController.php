<?php
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use app\stock\model\StockNewsModel;
// use app\stock\model\StockModel;
// use app\stock\model\StockIndiceModel;
use think\Db;

/*处理每日定时任务 \app\stock\controller\IndexController.php */
class TimeController extends HomeBaseController
{
    /*处理每日定时任务，crontab每3秒执行一次  */
    /*
     * 获取创业板、深圳、上海股票大盘指数
     */
    public function indice()
    {
        $m = Db::name('stock_indice');
        //上证指数、深证成指、创业板指
        $url = 'http://hq.sinajs.cn/list=s_sh000001,s_sz399001,s_sz399006';

        $content = cmf_curl_get($url);
        $data    = explode(';', $content);
        unset($data[3]);
        $pattern = '/(?<==").*?(?=")/ism';
        foreach ($data as $key => $val) {
            preg_match($pattern, $val, $arr);
            $tmp    = explode(',', $arr[0]);
            $post[] = [
                'id'      => $key + 1,
                'count'   => round($tmp[1], 2),
                'num'     => round($tmp[2], 2),
                'percent' => round($tmp[3], 2),
            ];
            // 不使用模型层时
            // $m->where('id', $key+1)->update([
            //     'count'   => round($tmp[1], 2),
            //     'num'     => round($tmp[2], 2),
            //     'percent' => round($tmp[3], 2),
            // ]);
        }
        // dump($post);die;

        // $m->insertAll($post);
        model('stock/StockIndice')->isUpdate(true)->saveAll($post);
        cmf_log('股市指数获取结束', 'stock_indice.log');
        exit('股市指数获取结束');
    }

    /*处理每日定时任务，crontab每日凌晨1点执行一次  */
    /**
     * 获取股票列表、更新
     * https://jingyan.baidu.com/article/ff411625d1433a12e5823772.html
     * A股
     *     沪市A股（就是上海A股交易市场上市的股票）：600xxx,601xxx,603xxx
     *     深市A股（就是深圳A股交易市场上市的股票）：000xxx
     * 中小板：深市中小板（主要为中小企业上市提供场所，帮助公司融资）：002xxx
     * 创业板（主要目的就是初创的公司，但是潜力具体的股票进行融资的地方）：300xxx
     * B股
     *     沪市B股主要格式为200xxx
     *     深市B股主要格式为900xxx
     * 权证代码：沪市的为58，深市的为031
     * 基金代码：沪市的为500开头，深市的为4开头
     * 配股代码：沪市的为700开头，深市的为8开头
     * 转配股代码：沪市的为710开头，深市的为3开头
     * 新股申购代码：沪市的为730开头，深市的即为该股票代码
     * 国债代码：沪市的为00开头，深市的为19开头
     * 企业债券代码：沪市的为12开头，深市为10开头
     * 回购债券：沪市的为2开头，深市的为1开头
     * @return [type] [description]
     */
    public function stock_list()
    {
        //股票列表
        $appKey      = '32258';
        $sign        = '813e13dfe768c1d9c75eaaba70d42c1a';
        $nowapi_parm = [
            'app'      => 'finance.stock_list',
            'category' => 'hs',
            'appkey'   => $appKey,
            'sign'     => $sign,
            'format'   => 'json',
        ];
        $result = $this->nowapi_call($nowapi_parm); //总计4705个股票代码
        if (empty($result['lists'])) {
            cmf_log('获取股票列表失败', 'stock_list.log');
            exit('获取股票列表错误');
        }

        $data_update = [];
        $data_insert = [];
        $time        = time();
        $m           = Db::name('stock');
        $data0       = $m->column('code0,name');

        // 股票代码过滤，保留A股:sh60*,sz00*,sz30*
        // $filter = 'sh201*~sh204*,sh500*~sh502*,sh505888,sh510*~sh513,sh518800,sh518880,sh580*,sh600*~sh601*,sh603*,sh900*,sz000*,sz001696,sz001896,sz001965,sz001979,sz002*,sz031005,sz031007,sz038011,sz038014~sz038017,sz131*,sz150*,sz159*,sz160*~sz169*,sz184*,sz200*,sz300*,sz500159';
        foreach ($result['lists'] as $k => $v) {
            if (in_array(substr($v['symbol'], 0, 4), ['sh60', 'sz00', 'sz30'])) {
                if (isset($data0[$v['symbol']])) {
                    if ($v['sname'] != $data0[$v['symbol']]) {
                        //股票更名
                        $data_update[$v['symbol']] = $v['sname'];
                    }
                } else {
                    $data_insert[] = [
                        'code0' => $v['symbol'],
                        'name'  => $v['sname'],
                        'time'  => $time,
                        'code'  => substr($v['symbol'], 2),
                    ];
                }
            }
        }

        $row_insert = 0;
        $row_update = count($data_update);
        if (!empty($data_insert)) {
            $row_insert = $m->insertAll($data_insert);
        }
        if (!empty($data_update)) {
            foreach ($data_update as $k => $v) {
                $m->where('code0', $k)->update(['name' => $v, 'time' => $time]);
            }
        }

        cmf_log('获取股票列表执行完成，新增' . $row_insert . '条，更新' . $row_update . '条', 'stock_list.log');
        exit('执行完成');
    }
    public function nowapi_call($a_parm)
    {
        if (!is_array($a_parm)) {
            return false;
        }
        //combinations
        $a_parm['format'] = empty($a_parm['format']) ? 'json' : $a_parm['format'];
        $apiurl           = empty($a_parm['apiurl']) ? 'http://api.k780.com/?' : $a_parm['apiurl'] . '/?';
        unset($a_parm['apiurl']);
        foreach ($a_parm as $k => $v) {
            $apiurl .= $k . '=' . $v . '&';
        }
        $apiurl = substr($apiurl, 0, -1);
        if (!$callapi = file_get_contents($apiurl)) {
            return false;
        }
        //format
        if ($a_parm['format'] == 'base64') {
            $a_cdata = unserialize(base64_decode($callapi));
        } elseif ($a_parm['format'] == 'json') {
            if (!$a_cdata = json_decode($callapi, true)) {
                return false;
            }
        } else {
            return false;
        }
        //array
        if ($a_cdata['success'] != '1') {
            return false;
        }
        return $a_cdata['result'];
    }

    /*处理每日定时任务，crontab每天8点半，12点半，19点半执行一次  */
    /**
     * 获取指定网站新闻
     * 删除一周前的数据
     * 详情见 \app\stock\controller\TestController - 2.php
     * @return [type] [description]
     */
    public function news()
    {
        // 证券时报网
        $deal[0]['source'] = ['type' => 1, 'name' => '证券时报网'];
        $deal[0]['url']    = [
            1 => 'http://stock.stcn.com/dapan/index.shtml', //大盘
            2 => 'http://stock.stcn.com/bankuai/index.shtml', //板块个股
            3 => 'http://stock.stcn.com/xingu/index.shtml', //新股动态
        ];
        $deal[0]['pattern'] = [
            "/<div class=\"mainlist\".*?>.*?<\/div>/ism",
            "/<li.*?>.*?<\/li>/ism",
            "/<p class=\"tit\"><a href=\"(.*?)\".*?>(.*?)<\/a><span>(.*?)<\/span><\/p>/ism",
            "/<div class=\"kx_left\">.*?<div class=\"intal_tit\">.*?<h2>(.*?)<\/h2>.*?<div class=\"info\">(.*?)<\/div>.*?<\/div>.*?<div class=\"txt_con\" id=\"ctrlfscont\">(.*?)<\/div>.*?<\/div>/ism",
            "\[\]",
        ];

        // 新浪财经的  暂时只能匹配博客的内容
        $deal[1]['source'] = ['type' => 2, 'name' => '新浪财经'];
        $deal[1]['url']    = [
            4 => 'http://finance.sina.com.cn/column/ggdp.shtml', //[博客]个股点评
        ];
        $deal[1]['pattern'] = [
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

        foreach ($deal as $ref) {
            // 从数据库中获取最近时间戳
            $maxTime = $scModel->where('type', $ref['source']['type'])->max('create_time');
            $time    = ['time' => $maxTime];
            if (is_array($ref['url'])) {
                foreach ($ref['url'] as $k => $v) {
                    $result += $scModel->creeper($v, $ref['pattern'], $ref['source'], $k, $time);
                }
            } else {
                $result += $scModel->creeper($ref['url'], $ref['pattern'], $ref['source'], 0, $time);
            }
        }

        // 删除7天之前的数据
        $dtime = strtotime('-7 day');
        $dnum = $scModel->where('create_time','lt',$dtime)->delete();

        cmf_log('更新 ' . $result . ' 条，删除 '.$dnum.' 条', 'news.log');
    }
}
