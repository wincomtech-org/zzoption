<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;
use app\portal\model\StockModel;
use app\portal\model\StockIndiceModel;
use app\portal\model\StockNewsModel;

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
        exit('股市指数获取结束');
    }

    /*处理每日定时任务，crontab每日凌晨1点执行一次  */
    /* 获取股票列表，更新 */
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
        $result = $this->nowapi_call($nowapi_parm);
dump($result);die;
        if (empty($result['lists'])) {
            cmf_log('获取股票列表失败', 'stock_list.log');
            exit('获取股票列表错误');
        }
        $data_update = [];
        $data_insert = [];
        $time        = time();
        $m           = Db::name('stock');
        $data0       = $m->column('code0,name');

        foreach ($result['lists'] as $k => $v) {
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

    /**
     * 获取指定网站新闻
     * 详情见 \app\stock\controller\TestController - 2.php
     * @return [type] [description]
     */
    public function news()
    {
        // 证券时报网
        $deal[0]['source'] = ['type'=>1,'name'=>'证券时报网'];
        $deal[0]['url'] = [
            1=>'http://stock.stcn.com/dapan/index.shtml', //大盘
            2=>'http://stock.stcn.com/bankuai/index.shtml', //板块个股
            3=>'http://stock.stcn.com/xingu/index.shtml', //新股动态
        ];
        $deal[0]['pattern'] = [
            "/<div class=\"mainlist\".*?>.*?<\/div>/ism",
            "/<li.*?>.*?<\/li>/ism",
            "/<p class=\"tit\"><a href=\"(.*?)\".*?>(.*?)<\/a><span>(.*?)<\/span><\/p>/ism",
            "/<div class=\"kx_left\">.*?<div class=\"intal_tit\">.*?<h2>(.*?)<\/h2>.*?<div class=\"info\">(.*?)<\/div>.*?<\/div>.*?<div class=\"txt_con\" id=\"ctrlfscont\">(.*?)<\/div>.*?<\/div>/ism",
            "\[\]"
        ];

        // 新浪财经的  暂时只能匹配博客的内容
        $deal[1]['source'] = ['type'=>2,'name'=>'新浪财经'];
        $deal[1]['url'] = [
            4=>'http://finance.sina.com.cn/column/ggdp.shtml', //[博客]个股点评
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
            $maxTime = $scModel->where('type',$ref['source']['type'])->max('create_time');
            $time = ['time'=>$maxTime];
            if (is_array($ref['url'])) {
                foreach ($ref['url'] as $k => $v) {
                    $result += $scModel->creeper($v,$ref['pattern'],$ref['source'],$k,$time);
                }
            } else {
                $result += $scModel->creeper($ref['url'],$ref['pattern'],$ref['source'],0,$time);
            }
        }

        cmf_log('更新'.$result.'条', 'news.log');
    }
}