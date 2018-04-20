<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class StockController extends HomeBaseController
{
    public function _initialize()
    {
        
        parent::_initialize();
    }
    
    /* 查询指数 */
    public function stock_indice(){
         
        $indices=Db::name('stock_indice')->column('id,count,num,percent');
        $this->success('获取成功','',$indices);
    }
    /* 查询股票列表 */
    public function stock_search(){
        $code=$this->request->param('code','','trim');
        $where=['status'=>['eq',1]];
        if($code>0){
            $where['code']=['like','%'.$code.'%'];
        }else{
            $where['name']=['like','%'.$code.'%'];
        }
        $list=Db::name('stock')->where($where)->limit(10)->column('code0,code,name');
        $this->success('获取成功','',$list);
    }
     
     
}
