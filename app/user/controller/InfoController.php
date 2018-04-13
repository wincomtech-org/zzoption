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
/* 个人中心 */
class InfoController extends UserBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        $user=session('user');
        $user=Db::name('user')->where('id',$user['id'])->find();
        session('user',$user);
        $this->assign('user',$user);
    }
    public function index(){
        $this->redirect(url('portal/index/my'));
    }
    
    
    /**
     * 用户信息
     */
    public function info()
    {
        
        $this->assign('html_title','个人信息');
        return $this->fetch();
        
    }
    
     
    /**
     * 保存紧急联系人信息
     */
    public function ajax_tels()
    {
        $m_user=Db::name('user');
        $uid=session('user.id');
        $user=$m_user->where('id',$uid)->find();
        $data0=$this->request->param('');
        if(($data0['tel1']==''||$data0['name1']=='') && ($data0['tel2']==''||$data0['name2']=='')){
           $this->error('父母信息最少要填一项');
        }
        
         
        $more=json_decode($user['more'],true);
        
        $more['tel1'] = $data0['tel1'];
        $more['name1']=$data0['name1'];
        $more['tel2']=$data0['tel2'];
        $more['name2']=$data0['name2'];
        $more['tel3'] = $data0['tel3'];
        $more['name3']=$data0['name3'];
        $more['tel4']=$data0['tel4'];
        $more['name4']=$data0['name4'];
        
        $data=['more'=>json_encode($more)];
        $m_user->where('id',$uid)->update($data);
       
        $this->success('保存成功',url('portal/index/trust'));
        
    }
    /**
     * 紧急联系人
     */
    public function bank()
    {
        $user=Db::name('user')->field('user_login,user_nickname,is_name,bank_name,bank_card')->where('id',session('user.id'))->find();
        if($user['bank_card']!=''){
            $tmp1=substr($user['bank_card'], 0,6);
            $tmp2=substr($user['bank_card'], -4);
            //$user['bank_card']=$tmp1.'******'.$tmp2;
            
        }
        $this->assign('html_title','添加银行卡');
        $this->assign('user',$user);
        return $this->fetch();
        
    }
    
    
    /* 头像修改 */
    public function ajax_avatar(){
        set_time_limit(300);
        $user=session('user');
        if(empty($_FILES['avatar1'])){
            $this->error('请选择图片');
        }
        $file=$_FILES['avatar1'];
       
        if($file['error']==0){
            if($file['size']>config('avatar_size')){
                $this->error('文件超出大小限制');
            }
            $avatar='avatar/'.$user['id'].'.jpg';
            $path=getcwd().'/upload/';
           
            $destination=$path.$avatar;
            if(move_uploaded_file($file['tmp_name'], $destination)){
                $avatar=zz_set_image($avatar,$avatar,100,100,6);
                if(is_file($path.$avatar)){ 
                   
                    $user['avatar']=$avatar;
                    Db::name('user')->where('id',$user['id'])->update(['avatar'=>$avatar]);
                    session('user',$user);
                    $this->success('上传成功',url('user/info/info'));
                }else{
                    $this->error('头像修改失败');
                }
            }else{
                $this->error('文件上传失败');
            }
        }else{
            $this->error('文件传输失败');
        }
    }
     
    /* 实名认证 */
    public function realname(){
        
        $this->assign('html_title','实名认证');
       
        return $this->fetch();
    }
    
    /* 实名认证 */
    public function ajax_name(){
        $data=$this->request->param();
        $rules = [ 
            'name'=>'require|chs|min:2',
        ];  
        $validate = new Validate($rules);
        $validate->message([
           
            'name.chs'=>'请填写真实姓名',
            'name.min'=>'请填写真实姓名',
            'name.require'=>'请填写真实姓名',
        ]);
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $data_user=[
            'user_login'=>$data['idcard'],
            'user_nickname'=>$data['name'],
            'is_name'=>1,
            'bank_card'=>$data['bank'],
        ];
        
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        
        import('idcard1',EXTEND_PATH);
        $idcard1= new \Idcard1();
        if(($idcard1->validation_filter_id_card($data_user['user_login']))!==true){
            $this->error('身份证号码非法!');
        }
        $tmp=$m_user->where(['user_login'=>['eq',$data_user['user_login']],'id'=>['neq',$uid]])->find();
        if(!empty($tmp)){
            $this->error('身份证号码已被占用');
        }
       
        try {
            $m_user->where('id',$uid)->update($data_user);
        } catch (\Exception $e) {
            $this->error('认证失败，请检查身份信息');
        }
         
        $user=$m_user->where('id',$uid)->find();
        session('user',$user);
        $this->success('认证成功',url('user/info/info'));
        
    }
    /* 修改密码*/
    public function psw(){
        $this->assign('html_title','修改密码');
        return $this->fetch();
    }
    /* 修改密码*/
    public function ajax_psw(){
        $data=$this->request->param('');
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw0']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        //修改密码
        if(preg_match(config('reg_psw'), $data['psw'])==1){
            $m_user->where('id',$uid)->update(['user_pass'=>cmf_password($data['psw'])]);
            $this->success('修改成功',url('user/info/setting'));
        }
        $this->error('修改失败');
        
    }
    /* 修改手机号*/
    public function mobile(){
        $this->assign('html_title','修改手机号');
        return $this->fetch();
    }
    /* 修改手机号*/
    public function ajax_mobile(){
        $data=$this->request->param('');
        $validate = new Validate([
             
            'code'  => 'require|number|length:6',
            'tel' => 'require|number|length:11',
            'psw' => 'require|number|length:6',
        ]);
        $validate->message([
            'tel.require'           => '手机号码错误',
            'tel.number'           => '手机号码错误',
            'tel.length'           => '手机号码错误', 
            'code.require'           => '短信验证码错误',
            'code.number'           => '短信验证码错误',
            'code.length'           => '短信验证码错误',
            'psw.require' => '密码为6位数字',
            'psw.number' => '密码为6位数字',
            'psw.length' => '密码为6位数字', 
        ]);
        
        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        } 
       
        if (preg_match(config('reg_mobile'), $data['tel'])) {
            $uid=session('user.id');
            $m_user=Db::name('user');
            //判断手机号
            $tmp=$m_user->where('mobile',$data['tel'])->find();
            if(!empty($tmp)){
                $this->error("您的手机号已存在");
            }
            //判断密码
            $user=$m_user->where('id',$uid)->find(); 
            $result=zz_psw($user, $data['psw']);
            if(empty($result[0])){
                $this->error($result[1],$result[2]);
            }
            //短信验证码
            $msg=new Msg();
            $res=$msg->verify($data['tel'],$data['code']);
            if($res!=='success'){
                $this->error($res);
            } 
            $m_user->where('id',$uid)->update(['mobile'=>$data['tel']]);
            session('user.mobile',$data['tel']);
            $this->success('手机号更改成功',url('user/info/setting'));
        } else {
            $this->error("您输入的手机号格式错误");
        }
         
    }
    /* 身份证照片 */
    public function pic(){
        $user=Db::name('user')->field('user_login')->where('id',session('user.id'))->find();
        $tmp='pic/'.md5($user['user_login']);
        $pic[1]=$tmp.'camera1.jpg';
        $pic[2]=$tmp.'camera2.jpg';
        $pic[3]=$tmp.'camera3.jpg';
        $this->assign('pic',$pic);
        $this->assign('html_title','身份认证');
        return $this->fetch();
    }
    /*  身份证照片 */
    public function ajax_pic(){
        set_time_limit(300);
        zz_log('pp','test.log');
        foreach($_FILES as $k=>$v){
            $file=$v;
            if($file['error']==0){
                if($file['size']>config('avatar_size')){
                    $this->error('文件超出大小限制');
                }
                $avatar='pic/'.md5(session('user.user_login')).$k.'.jpg';
                $path=getcwd().'/upload/';
                $size=config($k);
                $destination=$path.$avatar;
                if(move_uploaded_file($file['tmp_name'], $destination)){
                    $avatar=zz_set_image($avatar,$avatar,$size['width'],$size['height'],6);
                    if(!is_file($path.$avatar)){
                        $this->error('图片处理失败');
                    } 
                }else{
                    $this->error('文件上传失败');
                }
            }else{
                $this->error('文件传输失败,请注意图片大小和网速');
            }
        }
        $this->success('上传成功',url('portal/index/trust'));
         
    }
    public function setting(){
        $this->assign('user',session('user'));
        $this->assign('html_title','设置');
        return $this->fetch();
    }
    public function xuexin(){
        $this->error('功能暂无');
        $this->assign('html_title','学信网');
        return $this->fetch();
    }
    public function record(){
        $this->error('功能暂无');
        $this->assign('html_title','运营商评估');
        return $this->fetch();
    }
    
}
