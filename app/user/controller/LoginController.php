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

use think\Validate;
use cmf\controller\HomeBaseController;
use app\user\model\UserModel;
use sms\Msg;
class LoginController extends HomeBaseController
{

    /**
     * 登录
     */
    public function index()
    {
        $this->redirect(url('portal/index/info'));
        $redirect = $this->request->post("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            $redirect = base64_decode($redirect);
        }
        session('login_http_referer', $redirect);
        //
        
         if (cmf_is_user_login()) { //已经登录时直接跳到首页
             $this->redirect(url('portal/index/info'));
        } else {
            $this->redirect(url('user/login/login'));
        } 
    }
    /**
     * 登录
     */
    public function login()
    {
        $this->assign('html_title','登录');
       return $this->fetch();
        
    }

    /**
     * 登录验证提交
     */
    public function doLogin()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'captcha'  => 'require',
                'username' => 'require',
                'password' => 'require|number|length:6',
            ]);
            $validate->message([
                'username.require' => '用户名不能为空',
                'password.require' => '密码不能为空',
                'password.number'     => '密码为数字',
                'password.min'     => '密码为6位数字',
                'captcha.require'  => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            if (!cmf_captcha_check($data['captcha'])) {
                $this->error('验证码错误');
            }

            $userModel         = new UserModel();
            $user['user_pass'] = $data['password'];
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log                = $userModel->doEmail($user);
            } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->doMobile($user);
            } else {
                $user['user_login'] = $data['username'];
                $log                = $userModel->doName($user);
            }
            $session_login_http_referer = session('login_http_referer');
            $redirect                   = empty($session_login_http_referer) ? $this->request->root() : $session_login_http_referer;
            switch ($log) {
                case 0:
                    cmf_user_action('login');
                    $this->success('登录成功', $redirect);
                    break;
                case 1:
                    $this->error('登录密码错误');
                    break;
                case 2:
                    $this->error('账户不存在');
                    break;
                case 3:
                    $this->error('账号被禁止访问系统');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }
    /**
     * ajax登录验证提交
     */
    public function ajax_login()
    {
        
            $validate = new Validate([
                
                'tel' => 'require|number|length:11',
                'password' => 'require|min:6|max:20',
            ]);
            $validate->message([
                'tel.require' => '手机号错误',
                'tel.number' => '手机号错误',
                'tel.length' => '手机号错误',
                'password.require' => '密码不能为空',
                'password.min'     => '密码为6-20位',
                'password.max'     => '密码为6-20位',
               
                
            ]);
            
            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
           
            $userModel         = new UserModel();
            $user=['user_pass'=> $data['password']];
            if (preg_match(config('reg_mobile'), $data['tel'])) {
                $user['mobile'] = $data['tel'];
                $log = $userModel->doMobile($user);
            } else {
                $this->error('手机号格式错误'); 
            }
            //$session_login_http_referer = session('login_http_referer');
            $url=url('portal/index/index');
             switch ($log) {
                case 0:
                    //用户操作记录，用于计算在线积分等
                    cmf_user_action('login');
                  
                    $this->success('登录成功',$url);
                    break;
                case 1:
                    $this->error('登录密码错误');
                    break;
                case 2:
                    $this->error('账户不存在');
                    break;
                case 3:
                    $this->error('账号被禁止访问');
                    break;
                case 4:
                    $this->error('密码连续错误'.config('psw_fail').'次，请明天再登录');
                    break;
                default :
                    $this->error('未受理的请求');
            }
         
    }
    /**
     * 找回密码
     */
    public function findpass()
    {
        $this->assign('html_title','找回密码');
        return $this->fetch();
        
    }
    /**
     * 重置密码
     */
    public function ajax_findpsw()
    {
        
        $time=time();
        $verify=session('sms');
        $data1 = $this->request->post();
       
        //验证码
        if(empty($verify) ||($time-$verify['time'])>600){
            $this->error('验证码不存在或已过期');
        }
        if($verify['code']!=$data1['code']){
            $this->error('验证码错误');
        }
        if($verify['mobile']!=$data1['tel'] ){
            $this->error('手机号码不匹配');
        }
        
        $rules = [
            'user_pass' => 'require|min:6|max:20',
            'mobile'=>'require|number|length:11',
        ];
        $redirect                = url('user/login/login');
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
            'user_pass'=>$data1['psw'],
            'mobile'=>$data1['tel'],
           
        ];
        
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
         
        $userModel = new UserModel();
        if (preg_match(config('reg_mobile'), $data['mobile'])) { 
            $log            = $userModel->mobilePasswordReset($data['mobile'], $data['user_pass']);
        } else {
            $log = 2;
        }
        switch ($log) {
            case 0:
                session('user',null);
                session('sms',null);
                $this->success('密码重置成功',$redirect);
                break;
            case 1:
                $this->error("您的手机号尚未注册");
                break;
            case 2:
                $this->error("您输入的手机号格式错误");
                break;
            default :
                $this->error('未受理的请求');
        }
        
    
    }
 

}