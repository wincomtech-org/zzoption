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
        
        $list=[
            'money_off'=>['title'=>'名义本金<br/>（万元，不可选，用-分隔）','info'=>implode('-', config('money_off'))],
            'money_on'=>['title'=>'名义本金<br/>（万元，可选，用-分隔）','info'=>implode('-', config('money_on'))],
            'day'=>['title'=>'周期<br/>（月，用-分隔）','info'=>implode('-', config('day'))],
            'notice_day'=>['title'=>'期权到期提醒天数<br/>（持仓临近期限提醒）','info'=>config('notice_day')],
            'sell_day'=>['title'=>'期权买入后可行权天数<br/>（持仓超过此天数才可行权）','info'=>config('sell_day')],
            'psw_count'=>['title'=>'密码错误次数<br/>（超过将退出登录）','info'=>config('psw_count')],
            'psw_fail'=>['title'=>'密码错误次数<br/>（超过将不能登录，次日清零）','info'=>config('psw_fail')],
            
            
        ];
        $this->assign('list',$list);
        
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
       
        $data0= $this->request->param('','','trim');
        $data=[];
        $data['money_off']=explode('-',$data0['money_off']);
        foreach($data['money_off'] as $k=>$v){
            $i=intval($v);
            if($i<=0 || $i!=$v){
                $this->error('名义本金只能为大于0的整数，用-分隔');
            }
        }
        $data['money_on']=explode('-',$data0['money_on']);
        foreach($data['money_on'] as $k=>$v){
            $i=intval($v);
            if($i<=0 || $i!=$v){
                $this->error('名义本金只能为大于0的整数，用-分隔');
            }
        }
        $data['day']=explode('-',$data0['day']);
        foreach($data['day'] as $k=>$v){
            $i=intval($v);
            if($i<=0 || $i!=$v){
                $this->error('周期只能为大于0的整数，用-分隔');
            }
        }
        $data['notice_day']=intval($data0['notice_day']);
        $data['sell_day']=intval($data0['sell_day']);
        $data['psw_count']=intval($data0['psw_count']);
        $data['psw_fail']=intval($data0['psw_fail']);
     
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
