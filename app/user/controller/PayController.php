<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\UserBaseController;
use think\Db;
use think\Validate;
use sms\Msg;
use function Qiniu\json_decode;
/* 资金明细 */
class PayController extends UserBaseController
{
    private $m;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('money');
        
        $this->assign('html_flag','my');
    }
    /*充值提现*/
    public function index(){
        
        $this->assign('html_title','充值提现');
        return $this->fetch();
    }
    /*资金明细*/
    public function money(){
        $type=$this->request->param('type',1,'intval');
       
        $where=[
            'uid'=>session('user.id'),
            'type'=>$type,
            'status'=>1,
        ];
        $list=Db::name('money')->where($where)->order('time desc')->select();
        $this->assign('list',$list);
        $this->assign('type',$type);
        $this->assign('money_type',config('money_type'));
        $this->assign('html_title','资金明细');
        return $this->fetch();
    }
    
     
}
