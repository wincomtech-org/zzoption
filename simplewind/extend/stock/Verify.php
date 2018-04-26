<?php
header("Content-type: text/html; charset=utf-8");

/**
 * 实名认证 获取数据
 * @param array $params 请求的数据
 * 组合：姓名+身份证、姓名+银行卡、姓名+手机号、其它组合
 *  $params['bankcard'] = '银行卡号码';
 *  $params['realName'] = '姓名';
 *  $params['cardNo']   = '身份证号码';
 *  $params['Mobile']   = '手机号码,可为空';
 * @param $method
 * @return array|mixed
 */
function bankcardVerify($params = array(), $method = "GET")
{
    $url = 'https://aliyun-bankcard-verify.apistore.cn/bank';
    $set = config('aliapi');
    $appCode = $set['AppCode'];//appcode查看地址 https://market.console.aliyun.com/imageconsole/
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $method == "POST" ? $url : $url . '?' . http_build_query($params));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization:APPCODE ' . $appCode,
    ));
    if (stripos($url, "https://") !== false) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //CURL_SSLVERSION_TLSv1
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
    }
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if ($method == "POST") {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $callbcak = curl_exec($curl);
    $CURLINFO_HTTP_CODE = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    //如果返回的不是200,请参阅错误码 https://help.aliyun.com/document_detail/43906.html
    if ($CURLINFO_HTTP_CODE == 200) {
        return json_decode($callbcak, true);//['error_code'=0,'reason'=>'reason']
    } else if ($CURLINFO_HTTP_CODE == 403) {
        return array("error_code" => 403, "reason" => "剩余次数不足");
    } else if ($CURLINFO_HTTP_CODE == 400) {
        return array("error_code" => 400, "reason" => "APPCODE错误");
    } else {
        return array("error_code" => $CURLINFO_HTTP_CODE, "reason" => "APPCODE错误");
    }
}