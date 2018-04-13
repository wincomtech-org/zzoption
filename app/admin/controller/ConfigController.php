<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController;
 
use think\Db;

 
 
class ConfigController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
        
    }
     
    /**
     * 网站配置
     * @adminMenu(
     *     'name'   => '网站配置',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 15,
     *     'icon'   => '',
     *     'remark' => '网站配置',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        
        $info=[
            'zztitle'=>config('zztitle'),
            'rate'=>config('rate'),
            'rate_overdue'=>config('rate_overdue'),
            'tel1'=>config('tel1'),
            'tel2'=>config('tel2'),
            'paper_day' =>config('paper_day'),
            'paper_money' =>config('paper_money'),
            'company'=>config('company'),
            'pay_ali'=>config('pay_ali'),
            'pay_bank'=>config('pay_bank')
            
        ];
        $this->assign('info',$info);
        
        return $this->fetch();
    }
    
    /**
     * 网站配置编辑1
     * @adminMenu(
     *     'name'   => '网站配置编辑1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站配置编辑1',
     *     'param'  => ''
     * )
     */
    function editPost(){
       
        $data= $this->request->param();
        $tmp=explode('-',$data['paper_day']);
        foreach($tmp as $k=>$v){
            if(intval($v)<=0 || intval($v)!=$v){
                $this->error('借款时间只能为大于等于0的整数，用-分隔');
            }
        }
        $tmp=explode('-',$data['paper_money']);
        if(count($tmp)!=2 || $tmp[0]<0 || $tmp[0]>=$tmp[1] || 
            preg_match(config('reg_money'), $tmp[0])!==1 ||
            preg_match(config('reg_money'), $tmp[1])!==1){
            $this->error('借款金额范围格式为最小金额-最大金额');
        }
        if(preg_match('/^[0-9]{1,2}$/', $data['rate'])!==1 || 
            preg_match('/^[0-9]{1,2}$/', $data['rate_overdue'])!==1){
            $this->error('利率为0-99的整数');
         }
         $data['pay_ali']=[
             'id'=>$data['pay_ali_id'],
             'name'=>$data['pay_ali_name'],
             'title'=>'支付宝'
         ];
         $data['pay_bank']=[
             'id'=>$data['pay_bank_id'],
             'name'=>$data['pay_bank_name'],
             'title'=>$data['pay_bank_title'],
         ];
         unset($data['pay_ali_id']);
         unset($data['pay_ali_name']);
         unset($data['pay_bank_id']);
         unset($data['pay_bank_name']);
         unset($data['pay_bank_title']);
       
        $result=cmf_set_dynamic_config($data);
        if(empty($result)){
            $this->error('修改失败');
           
        }else{
            $data_action=[
                'aid'=>session('ADMIN_ID'),
                'time'=>time(),
                'type'=>'config',
                'ip'=>get_client_ip(),
                'action'=>'编辑网站配置',
            ];
            Db::name('action')->insert($data_action);
            $this->success('修改成功',url('index'));
        }
        
    }
     
     
}
