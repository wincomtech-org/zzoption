<?php
header("content-type:text/html;charset=utf-8");
// header("content-type:text/html;charset=gbk");
$appcode = "133ff1c380254976b638d37bf02d6fc0";
 
// +----------根据某个字段值对产品代码进行排序------------------------------------------------------------
//wangjikeji.com
// 接口调用示例代码 － 网极科技
error_reporting(0);
$host = "http://stock.api51.cn/sort";//如需https请修改为 https://smsapi51.cn
$path = "";//path为 single_sms_get 时为GET请求
$path = "";//path为 single_sms_get 时为GET请求
$method = "0";//post=1 get=0
/* en_hq_type_code:SS.ESA,SZ.ESA  选择沪深股市
sort_field_name:px_change_rate  按涨跌率排序
start_pos:0  起始位置
data_count:15  所需数据个数
sort_type:1  正序排序  */
$data=[
    'en_hq_type_code'=>'SS.ESA',
    'sort_field_name'=>'px_change_rate',
    'start_pos'=>0,
    'data_count'=>150,
    'sort_type'=>1,
];
$url = $host . $path;
/* if($_GET){
    $data = array(
        'en_hq_type_code'=>$_GET['en_hq_type_code'],//类型代码集 可以输入一个或者多个类型代码，使用逗号（,）连接。 类型代码可以是交易所识别码或交易所识别码和金融品种分类识别码两者组合而成， 详细请参阅《恒生金融云平台_商品代码命名规范》 。 示例： SS 表示上海证券交易所的所有代码, SS.ESA 表示上海证券交易所的所有 A 股 必须输入en_hq_type_code或en_prod_code。
        'en_prod_code'=>$_GET['en_prod_code'],//产品代码集	可以输入一个或多个代码证券代码包含交易所代码做后缀，作为该代码的唯一标识。如：600570.SS； 必须输入en_hq_type_code或en_prod_code，但en_prod_code优先级高于 en_hq_type_code。
        'sort_field_name'=>$_GET['sort_field_name'],//排序字段名称
        //按照该字段进行排序 允许的字段： 加权平均价：wavg_px 每股收益：eps 每股净资产：bps 昨收价：preclose_px 开盘价：open_px 最新价：last_px 最高价：high_px 最低价：low_px 成交金额：business_balance 成交数量：business_amount 成交笔数：business_count 国债基金净值：debt_fund_value 基金净值：iopv 涨跌额：px_change 涨跌幅：px_change_rate 振幅：amplitude 换手率：turnover_ratio 量比：vol_ratio 市盈率：pe_rate 市净率：dyn_pb_rate 市值：market_value 流通市值：circulation_value 内盘成交量：business_amount_in 外盘成交量：business_amount_out 委比：entrust_rate 委差：entrust_diff 财务季度：fin_quarter 财务截至日期：fin_end_date 总股本：total_shares 流通股本：circulation_amount 五分钟涨跌幅：min5_chgpct
        'sort_type'=>$_GET['sort_type'],//排序方式	0：表示升序； 1：表示降序(默认)
        'fields'=>$_GET['fields'],
        'start_pos'=>$_GET['start_pos'],//起始位置
        'data_count'=>$_GET['data_count'],//	数据个数
        'special_marker'=>$_GET['special_marker'],//	特殊标志
    );
}
if($_POST){
    $data = array(
        'en_hq_type_code'=>$_POST['en_hq_type_code'],//类型代码集 可以输入一个或者多个类型代码，使用逗号（,）连接。 类型代码可以是交易所识别码或交易所识别码和金融品种分类识别码两者组合而成， 详细请参阅《恒生金融云平台_商品代码命名规范》 。 示例： SS 表示上海证券交易所的所有代码, SS.ESA 表示上海证券交易所的所有 A 股 必须输入en_hq_type_code或en_prod_code。
        'en_prod_code'=>$_POST['en_prod_code'],//产品代码集	可以输入一个或多个代码证券代码包含交易所代码做后缀，作为该代码的唯一标识。如：600570.SS； 必须输入en_hq_type_code或en_prod_code，但en_prod_code优先级高于 en_hq_type_code。
        'sort_field_name'=>$_POST['sort_field_name'],//排序字段名称
        //按照该字段进行排序 允许的字段： 加权平均价：wavg_px 每股收益：eps 每股净资产：bps 昨收价：preclose_px 开盘价：open_px 最新价：last_px 最高价：high_px 最低价：low_px 成交金额：business_balance 成交数量：business_amount 成交笔数：business_count 国债基金净值：debt_fund_value 基金净值：iopv 涨跌额：px_change 涨跌幅：px_change_rate 振幅：amplitude 换手率：turnover_ratio 量比：vol_ratio 市盈率：pe_rate 市净率：dyn_pb_rate 市值：market_value 流通市值：circulation_value 内盘成交量：business_amount_in 外盘成交量：business_amount_out 委比：entrust_rate 委差：entrust_diff 财务季度：fin_quarter 财务截至日期：fin_end_date 总股本：total_shares 流通股本：circulation_amount 五分钟涨跌幅：min5_chgpct
        'sort_type'=>$_POST['sort_type'],//排序方式	0：表示升序； 1：表示降序(默认)
        'fields'=>$_POST['fields'],
        'start_pos'=>$_POST['start_pos'],//起始位置
        'data_count'=>$_POST['data_count'],//	数据个数
        'special_marker'=>$_POST['special_marker'],//	特殊标志
    );
} */

$data = http_build_query($data);

$result = api51_curl($url,$data,$method,$appcode);
echo $result;



function api51_curl($url,$data=false,$ispost=0,$appcode){
    $headers = array();
    //根据阿里云要求，定义 Appcode
    array_push($headers, "Authorization:APPCODE " . $appcode);
    array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
    
    $httpInfo = array();
    
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_USERAGENT , 'api51.cn' );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 300 );
    curl_setopt( $ch, CURLOPT_TIMEOUT , 300);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    if (1 == strpos("$".$url, "https://"))
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    if($ispost)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $data );
        curl_setopt( $ch , CURLOPT_URL , $url );
    }
    else
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        if($data){
            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$data );
            
        }else{
            curl_setopt( $ch , CURLOPT_URL , $url);
        }
        
    }
    $response = curl_exec( $ch );
    
    if ($response === FALSE) {
        // echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
    curl_close( $ch );
    return $response;
    
}
?>