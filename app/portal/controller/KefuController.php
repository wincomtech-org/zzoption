<?php
namespace app\portal\controller;

use cmf\controller\HomeBaseController;

/**
* 客服
* 独立链接 http://p.qiao.baidu.com/cps/chat?siteId=11930295&userId=23499205
*/
class KefuController extends HomeBaseController
{
    public function index()
    {

        return $this->fetch();
    }
}