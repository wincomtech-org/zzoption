<?php

/**
 * 获取最近交易日
 * @param  string $value [description]
 * @return [type]        [description]
 */
function trade5($date='20180419,20180420')
{
    $ret = lothar_nonTradingDay($date,3);
    // dump($ret);die;
}

/*
 * 获取非交易日
 * ---------------------------------------------------------------------------------
 * 比特福：http://tool.bitefu.net/jiari/?d=
 * 用法举例
检查一个日期是否为节假日 ?d=20180101
检查多个日期是否为节假日 ?d=20180101,20180103,20180105,20181201
获取2018年10月份节假日 ?d=201810
获取2018年所有节假日 ?d=2018
获取2018年1/2月份节假日 ?d=201801,201802
 * 参数说明
d：      日期。可传单个日期如20160101 也可能传月份如201601将获取整个月的信息 也可以传年 获取整年的信息。必填
apikey： VIP专享验证。免费apikey 123456。
type：   返回类型 0:全部(节假日合并显示),1:只返回休息日,2:只返回节假日,3:全部(节假日分开显示),list:返回节日编码列表,count:返回apikey当天调用次数
back：   返回数据。默认空。空或0，以 0 1 2 为结果返回
1:       返回节日编码
callback：返回jsonp格式数据 如 callback=jsonp。默认空
 * -----------------------------------------------------------------------------------
 * 使用 NowAPI 聚合数据：
http://api.k780.com/?app=life.workday&date=20150903&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=xml
 */
function lothar_nonTradingDay($date='2018', $type=1)
{
    // $url = 'http://tool.bitefu.net/jiari/?d=2018';
    // $url = 'http://tool.bitefu.net/jiari/?d=201804';
    // $url = 'http://tool.bitefu.net/jiari/?d=201804,201802';
    // $url = 'http://tool.bitefu.net/jiari/?d=20180408,20180405,20180407';
    $url = 'http://tool.bitefu.net/jiari/?d='.$date;

    $count = 0;

    if ($type == 1) { //年
        $date = '2018,2019';
        $unwork = '{"2018":{"0101":"2","0215":"1","0216":"2","0217":"2","0218":"2","0219":"1","0220":"1","0221":"1","0405":"2","0406":"1","0407":"1","0429":"1","0430":"1","0501":"2","0616":"1","0617":"1","0618":"2","0922":"1","0923":"1","0924":"2","1001":"2","1002":"2","1003":"2","1004":"1","1005":"1","1006":"1","1007":"1"},"2019":false}';
        $arr = json_decode($unwork, true);
        // dump($arr);die;
    } elseif ($type == 2) { //月
        // $date = '201804,201805';
        // $unwork = '{"201804":{"0405":"2","0406":"1","0407":"1","0429":"1","0430":"1"},"201805":{"0501":"2"}}';
        $unwork = cmf_curl_get($url);
        $arr  = json_decode($unwork, true);
        // dump($arr);die;
    } else { //日
        // $date = '20180404,20180405,20180406,20180407,20180408,20180409';
        // $unwork = '{"20180404":"0","20180405":"2","20180406":"1","20180407":"1","20180408":"0","20180409":"0"} ';
        $data = cmf_curl_get($url);
        $arr = json_decode($data, true);
        // dump($arr);die;

        $count = array_count_values($arr);
        if (isset($count[0]) && $count[0] > 0) {
            echo "含有{$count[0]}个工作日<br>";
        }
        if (isset($count[1]) && $count[1] > 0) {
            echo "含有{$count[1]}个休息日<br>";
        }
        if (isset($count[2]) && $count[2] > 0) {
            echo "含有{$count[2]}个节假日<br>";
        }
        exit();
    }
    
    return $arr;
}

/**
 * 字符转码
 * GBK的code page是CP936
 * iconv('GBK','UTF-8//IGNORE',$str);
 * @param  string $str  [字符串]
 * @param  string $code [目标编码]
 * @return [type]       [description]
 */
function lothar_transCoding($str = '', $code = 'UTF-8')
{
    $encode = mb_detect_encoding($str, array("ASCII", "GB2312", "BIG5", "GBK", "UTF-8"));
    if ($encode != $code) {
        // $str = iconv("GBK",$code,$str);
        $str = mb_convert_encoding($str, $code, "GBK");
    }

    return $str;
}
