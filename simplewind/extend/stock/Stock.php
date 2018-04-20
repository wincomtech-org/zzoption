<?php
namespace stock;

/**
 * 股票数据模型
 * https://zhidao.baidu.com/question/166686795.html
 * K线图
 * 
 * @deprecated 不同数字开头代表不同票种，返回数据可能不一样
 * sh000001
 * var hq_str_sh000001="上证指数,0.0000,3066.7967,3066.7805,0.0000,0.0000,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,2018-04-18,09:18:15,00";
 * s_sh000001
 * var hq_str_s_sh000001="上证指数,3066.7805,-0.0162,-0.00,0,0";
 * sh600001
 * var hq_str_sh600001="邯郸钢铁,0.000,0.000,0.000,0.000,0.000,0.000,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,0,0.000,2018-04-13,11:45:01,-3";
 * s_sh600001
 * var hq_str_s_sh600001="";
 */
class Stock
{
    /**
     * 获取股票价格
     * s_开头  [S内盘指数]
     * s_sh600001  sh600001
     * @param  string $code [description]
     * @return [type]       [description]
     */
    public function getPrice($code='s_sh600000,s_sh600006')
    {
        $data = $this->getStockBase($code);
        $tmp = [];
        foreach ($data as $row) {
            $tmp[] = [
                'name'    => $row[1],
                'price'   => round($row[2], 2),
                'percent' => round($row[4], 2),
            ];
        }

        return $tmp;
    }

    /**
     * 获取大盘指数 
     * s_开头  [S内盘指数]
     * s_sh000001
     * @param  string $code [description]
     * @return [type]       [description]
     */
    public function getIndice($code='s_sh000001,s_sz399001,s_sz399006')
    {
        $data = $this->getStockBase($code);
        $tmp = [];
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
                if (!empty($arr[0])) {
                    $tmp    = explode(',', $arr[0]);
                    // $tmpNums= array_push($tmp,$codes[$key]);
                    $post[] = array_merge([$codes[$key]],$tmp);
                }
            }
        }
        // dump($post);die;

        // $m->insertAll($post);
        // model('StockIndice')->isUpdate(true)->saveAll($post);

        return $post;
    }

    /**
     * 获取股票列表
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
    public function nowapi_call($a_parm=[])
    {
        $set = config('nowapi_stock');
        // 预设值
        $a_parm = [
            'app'      => 'finance.stock_list',
            'category' => 'hs',
            'appkey'   => $set['appKey'],
            'sign'     => $set['sign'],
            'format'   => 'json',
        ];


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
}