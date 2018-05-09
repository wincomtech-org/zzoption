<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class TestController extends AdminBaseController
{
    private $m;
    private $order;
    private $order_status;
    public function _initialize()
    {
        parent::_initialize();
       
    }
     
    /**
     * 测试
     * @adminMenu(
     *     'name'   => '测试',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '测试',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=Db::name('order');
        $id=1;
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('此订单不存在');
        }
        $order_status=$this->order_status;
        $tmp_status=[];
        //当前价格和浮盈
        
        $stock=new \stock\Stock();
        $prices=$stock->getPrice('s_'.$info['code0']);
        $price=empty($prices['s_'.$info['code0']])?null:$prices['s_'.$info['code0']];
        $info['price2_tmp']=$price['price'];
        dump($prices);
        dump($info);
        $prices=$stock->getPrice('s_'.$info['code0'].',');
        dump($prices);
        $prices=$stock->getPrice('s_sz300369,s_sz600271');
        dump($prices);
        exit;
    }
     
}
