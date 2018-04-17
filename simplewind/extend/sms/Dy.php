<?php
namespace sms;
use sms\aliyun\SignatureHelper;

/**
* 阿里大于类
*/
class Dy
{
    private $url = 'dysmsapi.aliyuncs.com';
    private $accessKeyId = 'LTAIjpLMgGy1TvjR';
    private $accessKeySecret = 'Y8PkgNNLNbah407yol61nqdwHCeKFS';
    private $signname = '耀华科技';

    function __construct()
    {
        # code...
    }

    public function dySms($mobile='',$extra=['tc'=>'SMS_127810124'])
    {
        $accessKeyId     = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $url             = $this->url;
        $params          = [];

        if (isset($extra['sd'])) {
            $params = $this->querySendDetails($mobile,$extra);
        } else {
            $code = rand(1000, 9999);
            session('smsCode', $code);
            if (is_array($mobile)) {
                $params = $this->batchSms($mobile,$extra,$code);
            } else {
                $params = $this->oneSms($mobile,$extra,$code);
            }
        }

        // 合并固定参数
        $params = array_merge($params, [
            "RegionId" => "cn-hangzhou",
            "Version" => "2017-05-25",
        ]);
        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
        // 此处可能会抛出异常，注意catch
        $content = $helper->request($accessKeyId, $accessKeySecret, $url, $params,false);

        // dump($content);die;
        return $content;
    }

    public function oneSms($mobile = '18715511536', $extra = ['tc' => 'SMS_127810124'], $code)
    {
        $params = [
            'PhoneNumbers'  => $mobile,
            'SignName'      => $this->signname, //短信签名
            'TemplateCode'  => $extra['tc'], //短信模板ID
            'TemplateParam' => '{"code":"' . $code . '","product":"recharge"}', //可选，短信模板变量替换JSON串，如果有则需要，没有就不需要。不能是汉字！
            // 'OutId' => 'abcdefgh',//可选，外部流水扩展字段
            'Action'        => 'SendSms',
        ];

        return $params;
    }

    /**
     * 批量发送短信
     */
    function batchSms($mobile = ['18715511536','15261541317'], $extra = ['tc' => 'SMS_127810124',[['name'=>'lothar','price'=>'1.00'],['name'=>'zz','price'=>'1.00']]], $code) {

        // *** 需用户填写部分 ***

        // fixme 必填: 待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $params["PhoneNumberJson"] = $mobile;

        // fixme 必填: 短信签名，支持不同的号码发送不同的短信签名，每个签名都应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        // $params["SignNameJson"] = array(
        //     "云通信",
        //     "云通信2",
        // );
        foreach ($mobile as $value) {
            $params["SignNameJson"][] = $this->signname;
        }

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $extra['tc'];

        // fixme 必填: 模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        foreach ($extra['tp'] as $row) {
            $params['TemplateParamJson'][] = [
                'name'  => $row['name'],
                // 'price' => $row['price'],
                'code'  => $code
            ];
        }

        // todo 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        // $params["SmsUpExtendCodeJson"] = json_encode(array("90997","90998"));


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        $params["PhoneNumberJson"] = json_encode($params["PhoneNumberJson"], JSON_UNESCAPED_UNICODE);
        $params["SignNameJson"] = json_encode($params["SignNameJson"], JSON_UNESCAPED_UNICODE);
        $params["TemplateParamJson"]  = json_encode($params["TemplateParamJson"], JSON_UNESCAPED_UNICODE);

        if(!empty($params["SmsUpExtendCodeJson"] && is_array($params["SmsUpExtendCodeJson"]))) {
            $params["SmsUpExtendCodeJson"] = json_encode($params["SmsUpExtendCodeJson"], JSON_UNESCAPED_UNICODE);
        }

        $params['Action'] = 'SendBatchSms';

        return $params;
    }

    /**
     * 短信发送记录查询
     */
    function querySendDetails($mobile='18715511536',$extra=['sd'=>'20180417']) {
        $params = [
            'PhoneNumber'   => $mobile,//必填: 短信接收号码
            'SendDate'      => $extra['sd'],//必填: 短信发送日期，格式Ymd，支持近30天记录查询
            'PageSize'      => 10,//必填: 分页大小
            'CurrentPage'   => 1,//必填: 当前页码
            // 'BizId'         => 'lothar', //可选: 设置发送短信流水号
            'Action'        => 'QuerySendDetails',
        ];

        return $params;
    }
}