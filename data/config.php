<?php	return array (
  
  'reg_mobile' => '/(^(13\\d|15[^4\\D]|17[013678]|18\\d)\\d{8})$/',
  'reg_money' => '/^\\d{1,8}(.[0-9]{1,2})?$/',
  'reg_psw' => '/^\\D{6,20}$/',
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
    8=>'行权过期',
  ),
   'notice_time'=>5,
  
  'action_types' => 
  array (
    'order' => '订单', 
    'user' => '用户',
    'stock' => '股票',
    'date' => '日历',
    'config' => '网站配置',
    'system' => '系统任务',
  ),
  'guide_types' => 
  array (
    1 => '相关协议',
    2 => '新手课堂',
    
  ),
 'stock_status'=>[
     1=>'正常',
     2=>'停盘',
     3=>'不在业务范围',
     4=>'已废弃', 
 ],
  'avatar_size' => '2048000',
   'pic_banner'=>[
       'width'=>750,
       'height'=>180,
   ],
    'msg_status'=>[
        1=>'未查看',
        2=>'已读', 
    ],
    'msg_type'=>[
        1=>'系统消息',
        2=>'个人消息',
    ],
  
);