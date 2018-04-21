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

use cmf\controller\HomeBaseController;
use think\Validate;
use think\Db;
 

class RegisterController extends HomeBaseController
{

    /**
     * 前台用户注册
     */
    public function index()
    {
         
        if (cmf_is_user_login()) {
            $this->redirect(url('user/index/index'));
        } else {
           
           $this->redirect(url('register'));
        }
    }
    /**
     * 前台用户注册页面
     */
    public function register()
    {
       
        $this->assign('html_title','注册');
       return $this->fetch();
         
    }
    /**
     * 发送验证码
     */
    public function sendmsg()
    { 
        $phone=$this->request->param('tel',0);
        $type=$this->request->param('type','reg'); 
        
        $tmp=Db::name('user')->where('mobile',$phone)->find(); 
        switch ($type){
            //注册
            case 'reg': 
                if(!empty($tmp)){
                    $this->error('该手机号已被使用');
                } 
                break;
            //找回密码
            case 'find': 
                if(empty($tmp)){
                    $this->error('该手机号不存在');
                } 
                break;
            //换手机号
            case 'mobile':
                if(!empty($tmp)){
                    $this->error('该手机号已被使用');
                }
                //判断密码
                $psw=$this->request->param('psw',0);
                $user=Db::name('user')->where('id',session('user.id'))->find();
                
                break;
            default:
                 $this->error('未知操作');
                 
        }
       
        $tmp=\sms\Dy::dySms($phone);
      
        if(empty($tmp['code'])){
            $this->error('error');
        }elseif(trim($tmp['code'])=='OK'){
            $this->success('发送成功','');
        }else{
            $this->error($tmp['msg']);
        }
        
    }
    
    /**
     * 前台用户注册提交
     */
    public function ajax_register()
    {
        
        $time=time();
        $verify=session('sms');
        $data1 = $this->request->post();
        $shop= $data1['code']-10000;
        if($shop<=0){
            $this->error('机构码错误');
        }
        //验证码
        if(empty($verify) ||($time-$verify['time'])>600){
            $this->error('验证码不存在或已过期');
        }
        if($verify['code']!=$data1['sms']){
            $this->error('验证码错误');
        }
        if($verify['mobile']!=$data1['tel'] ){
            $this->error('手机号码不匹配');
        }  
        
        $rules = [ 
            'user_pass' => 'require|min:6|max:20',
            'mobile'=>'require|number|length:11', 
        ];
        $redirect                = url('portal/index/index');
        $validate = new Validate($rules);
        $validate->message([
            'user_pass.require' => '密码不能为空',
            'user_pass.min'     => '密码为6-20位',
            'user_pass.max'     => '密码为6-20位', 
            'mobile.number'     => '手机号码格式错误',
            'mobile.require' => '手机号码不能为空',
            'mobile.length'     => '手机号码格式错误', 
            
        ]);
        $data=[ 
            'user_pass'=>$data1['password'],
            'mobile'=>$data1['tel'],
            'shop'=>$shop,
            'last_login_ip'   => get_client_ip(0, true),
            'create_time'     => $time,
            'last_login_time' => $time,
            'user_status'     => 1,
            "user_type"       => 2,//会员
            'avatar'=>'avatar.jpg', 
            
        ];
        
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if(preg_match(config('reg_mobile'), $data1['tel'])!=1){
            $this->error('手机号码错误');
        }
        
        $tmp=Db::name('shop')->where('id',$shop)->find();
        if(empty($tmp)){
            $this->error('无效机构码');
        }
        
        $data['user_pass'] = cmf_password($data['user_pass']);
        
        $m_user=Db::name('user');
        $tmp=$m_user->where('mobile',$data['mobile'])->find();
        if(!empty($tmp)){
            $this->error('该手机号已被使用');
        }
        
        $result  = $m_user->insertGetId($data);
        if ($result !== false) {
            $data   = Db::name("user")->where('id', $result)->find();
            cmf_update_current_user($data);
            session('sms',null);
            $this->success("注册成功！",$redirect);
        } else {
            $this->error("注册失败！");
        }
        
    }
    
}