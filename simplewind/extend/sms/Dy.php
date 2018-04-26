<?php
namespace sms;
use sms\aliyun\SignatureHelper;

// ini_set("display_errors", "on");
// error_reporting(E_ALL);
// set_time_limit(0);
// header("Content-Type: text/plain; charset=utf-8");

/**
* 阿里大于类
*/
class Dy
{
    private static $url = 'dysmsapi.aliyuncs.com';
    private static $accessKeyId = '';
    private static $accessKeySecret = '';
    private static $signname = '';
    private static $templateIds = '';

    function __construct()
    {
        $set = config('dySms');
        self::$accessKeyId = $set['id'];
        self::$accessKeySecret = $set['secret'];
        self::$signname = $set['signname'];
        self::$templateIds = $set['templateIds'];
    }

    /**
     * 一条短信
     * @param  string $mobile [手机号]
     * @param  string $tc     [短信模板ID]
     * @param  string $tp     [实际替换内容]
     * @return [type]         [description]
     */
    public static function dySms($mobile = '', $tc = 'register', $tp=[])
    {
        $tcid = self::$templateIds[$tc];
        $params          = [];

        $params['PhoneNumbers'] = $mobile;
        $params['SignName'] = self::$signname;//短信签名
        $params['TemplateCode'] = $tcid;//短信模板ID
        // $params['OutId'] = '';//可选，外部流水扩展字段
        $params['Action'] = 'SendSms';//

        // 注意：这里只是示例。具体依据短信模板来设定替换变量
        if (in_array($tc,['register','pwd','phone'])) {
            $code = rand(1000, 9999);
        }

        $params['TemplateParam'] = self::Orz($tp);
        // dump($params);die;
        $content = self::base($params);
        if (isset($code)) {
            if ($content->Code=='OK') {
                $msg = session('sms');
                $last_time = isset($msg['time'])?$msg['time']:0;
                $last_mobile = isset($msg['mobile'])?$msg['mobile']:'';
                $time = time();
                if(!empty($msg) && $last_mobile==$mobile && ($time-$msg['time'])<60){
                    return ['code'=>'err','msg'=>'不要频繁发送'];
                }
                //保存短信信息
                session('sms', ['mobile'=>$mobile,'code'=>$code,'time'=>$time]);
            }
        }

        // dump($content);die;
        return ['code'=>$content->Code,'msg'=>$content->Message];
    }

    /**
     * [batchSms 批量发送短信]
     * @param  array  $mobile [手机号]
     * @param  array  $tc  [短信模板ID]
     * @param  array  $tp  [模板中变量替换规则]
     * $tp = [['name'=>'lothar','price'=>'1.00'],['name'=>'zz','price'=>'1.00']]
     * @return [type]         [description]
     */
    public static function batchSms($mobile = ['13356895214','18952143687'], $tc='register', $tp = []) {

        // *** 需用户填写部分 ***

        // fixme 必填: 待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $params["PhoneNumberJson"] = $mobile;

        // fixme 必填: 短信签名，支持不同的号码发送不同的短信签名，每个签名都应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        // $params["SignNameJson"] = array(
        //     "云通信",
        //     "云通信2",
        // );
        foreach ($mobile as $value) {
            $params["SignNameJson"][] = self::$signname;
        }

        $tcid = self::$templateIds[$tc];
        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $tcid;


        // fixme 必填: 模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        
        // 短信验证码
        // $code = '';
        // if (in_array($tc,['register','pwd','phone'])) {
        //     $code = rand(1000, 9999);
        // }
        $params['TemplateParamJson'] = self::Orz($tp,$code);


        // todo 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        // $params["SmsUpExtendCodeJson"] = json_encode(array("90997","90998"));


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        $params["PhoneNumberJson"] = json_encode($params["PhoneNumberJson"], JSON_UNESCAPED_UNICODE);
        $params["SignNameJson"] = json_encode($params["SignNameJson"], JSON_UNESCAPED_UNICODE);

        if(!empty($params["SmsUpExtendCodeJson"] && is_array($params["SmsUpExtendCodeJson"]))) {
            $params["SmsUpExtendCodeJson"] = json_encode($params["SmsUpExtendCodeJson"], JSON_UNESCAPED_UNICODE);
        }

        $params['Action'] = 'SendBatchSms';

        $content =  self::base($params,2);

        // dump($content);die;
        return ['code'=>$content->Code,'msg'=>$content->Message];
    }

    /**
     * 短信发送记录查询
     */
    public static function querySendDetails($mobile='', $sd='20180417') {
        $params = [
            'PhoneNumber'   => $mobile,//必填: 短信接收号码
            'SendDate'      => $sd,//必填: 短信发送日期，格式Ymd，支持近30天记录查询
            'PageSize'      => 10,//必填: 分页大小
            'CurrentPage'   => 1,//必填: 当前页码
            // 'BizId'         => 'lothar', //可选: 设置发送短信流水号
            'Action'        => 'QuerySendDetails',
        ];

        $content = self::base($params,3);

        // dump($content);die;
        return ['code'=>$content->Code,'msg'=>$content->Message];
    }

    /**
     * 模板处理
     * @param [type] $params [description]
     */
    private static function Orz($tp,$code=null)
    {
        //可选，短信模板变量替换JSON串，如果有则需要，没有就不需要。验证码不能是汉字！
        $count = self::getmaxdim($tp);
        if ($count==1) {
            $tparr = [
                'code'  => isset($code)?$code:'',
                'name'  => isset($tp['name'])?$tp['name']:'',
                'indent'=> isset($tp['indent'])?$tp['indent']:'',
                'content'=>isset($tp['content'])?$tp['content']:'',
            ];
        } else {
            foreach ($tp as $row) {
                $tparr[] = [
                    'code'  => isset($code)?$code:'',
                    'name'  => isset($tp['name'])?$tp['name']:'',
                    'indent'=> isset($tp['indent'])?$tp['indent']:'',
                    'content'=>isset($tp['content'])?$tp['content']:'',
                ];
            }
        }

        $tpjson  = json_encode($tparr,JSON_UNESCAPED_UNICODE);
        // $tparr = '{"code":"'. $code .'"}';

        return $tpjson;
    }

    /**
     * 统一查询接口
     * @param $type 1单条发送、2批量、3查询
     * 返回成功参数
        object(stdClass)#19 (4) {
          ["Message"] => string(2) "OK"
          ["RequestId"] => string(36) "E5D8BD87-93EE-4B1F-AF1C-A580A03EE208"
          ["BizId"] => string(20) "433401724276619532^0"
          ["Code"] => string(2) "OK"
        }
     * 返回失败
        object(stdClass)#19 (3) {
          ["Message"] => string(34) "18715511536s invalid mobile number"
          ["RequestId"] => string(36) "6045360A-D6B5-458F-A849-50141E19E1E7"
          ["Code"] => string(25) "isv.MOBILE_NUMBER_ILLEGAL"
        }
     * @param [type] $params [description]
     */
    public static function base($params,$type=1)
    {
        // 合并固定参数
        $params = array_merge($params, [
            "RegionId" => "cn-hangzhou",
            "Version" => "2017-05-25",
        ]);

        $accessKeyId     = self::$accessKeyId;
        $accessKeySecret = self::$accessKeySecret;
        $url             = self::$url;

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
        // 此处可能会抛出异常，注意catch
        $content = $helper->request($accessKeyId, $accessKeySecret, $url, $params,false);

        return $content;
    }

    /**
     * 返回数组的维度
     * @param  [type] $vDim [description]
     * @return [type]       [description]
     */
    public static function getmaxdim($vDim)
    {
        if (!is_array($vDim)) {
            return 0;
        } else {
            $max1 = 0;
            // 对每一个子集数组循环判断
            foreach ($vDim as $item) {
                $t1 = self::getmaxdim($item);
                if ($t1 > $max1) $max1 = $t1;
            }
            return $max1 + 1;
        }
    }
}