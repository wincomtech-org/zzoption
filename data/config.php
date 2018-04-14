<?php	return array (
  'zztitle' => '个股期权tt',
  
  'rate' => '24',
  'rate_overdue' => '36',
  'reg_mobile' => '/(^(13\\d|15[^4\\D]|17[013678]|18\\d)\\d{8})$/',
  'reg_money' => '/^\\d{1,8}(.[0-9]{1,2})?$/',
  'reg_psw' => '/^\\d{6}$/',
  'reg_bank' => '/^[0-9]{12,20}$/',
  'mobile_page' => '6',
  
  'money_off' => 
  array (
    0 => 20,
    1 => 50,
     
  ),
    'money_on' =>
    array (
        0 => 100,
        1 => 200,
        2=>300,
        3=>400,
    ),
    'day'=>
    array (
        0 => 1,
        1 => 2,
        2=>3,
    ),
  'order_status' => 
  array (
    0 => '询价中',
    1 => '询价成功',
    2 => '询价失败',
    3 => '已付款',
    4 => '持仓中',
    5 => '买入失败',
    6=>'行权中',
    7=>'行权结束',
    8=>'过期',
  ),
   'notice_time'=>5,
  
  'action_types' => 
  array (
    'paper' => '借款',
    'reply' => '申请',
    'user' => '用户',
    'config' => '网站配置',
    'system' => '系统任务',
  ),
  'guide_types' => 
  array (
    1 => '相关协议',
    2 => '新手课堂',
    
  ),
  'paper_day' => '7-14-21-28',
  'paper_money' => '100-6000',
  'time_day' => '2018-02-23',
  'avatar_size' => '2048000',
  'sms_id' => '9546656',
  'sms_psw' => 'mm147258',
  'wx_appid' => 'wxb530432b8f9c94f2',
  'wx_appsecret' => 'b337483ac1b023f116f901511518b51f',
  'camera1' => 
  array (
    'width' => 400,
    'height' => 300,
  ),
  'camera2' => 
  array (
    'width' => 400,
    'height' => 300,
  ),
  'camera3' => 
  array (
    'width' => 800,
    'height' => 600,
  ),
  'pay_ali' => 
  array (
    'id' => '15781971916',
    'name' => '王坤',
    'title' => '支付宝',
  ),
  'pay_bank' => 
  array (
    'id' => '6224321456321456321',
    'name' => '王坤',
    'title' => '中国建设银行',
  ),
);