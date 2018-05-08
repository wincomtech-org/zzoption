<?php
use think\Config;
use think\Db;
use think\Url;
use dir\Dir;
use think\Route;
use think\Loader;
use think\Request;
use cmf\lib\Storage;
 
// 应用公共文件

//设置插件入口路由
Route::any('plugin/[:_plugin]/[:_controller]/[:_action]', "\\cmf\\controller\\PluginController@index");
Route::get('captcha/new', "\\cmf\\controller\\CaptchaController@index");

 
/* 根据当前时间得到今天凌晨的时间 */
function zz_get_time0(){
    $day=date('Y-m-d',time());
    return strtotime($day);
}
 
 /* 根据期初价格，期末价格和名义本金计算收益 */
function zz_get_money($price1, $price2, $money0){
    if($price1<=0){
        return 0;
    }
    $tmp1=bcsub($price2, $price1,4);
    if($tmp1<=0){
        return 0;
    }
    $tmp2=bcmul($tmp1, $money0*10000,4);
    return bcdiv($tmp2,$price1,2);
}
/* 用户通知内容拼接 */
function zz_msg_dsc($info){
    return '('.$info['name'].'('.$info['code0'].'),名义本金'.$info['money0'].'万元,周期'.$info['month'].'个月)';
    //return $info['name'];
 
}
/* 用户通知 */
function zz_msg($data){
    //先保存消息内容再保存用户消息连接
    $data_txt=[
        'aid'=>$data['aid'],
        'title'=>$data['title'],
        'content'=>$data['content'],
        'type'=>$data['type'],
        'time'=>time(),
    ];
    $msg_id=Db::name('msg_txt')->insertGetId($data_txt);
     
    $data_msg=[
        'msg_id'=>$msg_id,
        'uid'=>$data['uid']
    ];
    Db::name('msg')->insert($data_msg);
    $sms=new \sms\Dy();
    $data_sms=[
        'name'=>empty($data['uname'])?$data['mobile']:$data['uname'],
        
    ];
    if(empty($data['sms'])){
        $data['sms']='order';
    }
    switch($data['sms']){
        case 'order':
            $data_sms['indent']=$data['title'];
            
            $sms_type='order';
            break;
        case 'money':
            $data_sms['content']=$data['title'];
            $sms_type='withdraw';
            break; 
        default:
            return 0;
            break;
            
    }
    
    $result=$sms->dySms($data['mobile'],$sms_type,$data_sms);
    cmf_log('短信'.$data['mobile'].'发送开始');
    foreach($result as $k=>$v){
        cmf_log($k.'--'.$v);
    }
    cmf_log('短信'.$data['mobile'].'发送结束');
    
}
/* 批量发送消息 */
function zz_msgs($data){
    //先保存消息内容再保存用户消息连接
    if(empty($data['data'])){
        return 0;
    }
    
    $time=time();
    $m_msg_txt=Db::name('msg_txt');
    $data_txt=[
        'aid'=>$data['aid'],
         'title'=>$data['title'],  
        'type'=>$data['type'],
        'time'=>$time,
    ];
    $data_sms=[];
    $mobiles=[];
    $sms_data=[];
    //批量发送的都是订单
    
    $sms_type=empty($data['sms'])?'order':$data['sms'];
    foreach($data['data'] as $k=>$v){
        $data_txt['content']=zz_msg_dsc($v).$data['title'];
        $msg_id=$m_msg_txt->insertGetId($data_txt);
        $data_msg[]=[
            'msg_id'=>$msg_id,
            'uid'=>$v['uid']
        ]; 
        $mobiles[]=$v['mobile'];
        $data_sms[]=[
            'name'=>$v['uname'],
            'indent'=>$data['title'],
        ];
        
    } 
    $sms=new \sms\Dy();
    //批量短信一个号码只能发一次，重复的不发
    $result=$sms->batchSms($mobiles,$sms_type,$data_sms);
     
    return Db::name('msg')->insertAll($data_msg);
    
}
/* 更新分站信息 */
function zz_shop($shop){
    
    $m_shop=Db::name('shop');
    // 分站信息更新 
    $shop0=$m_shop->where('id',1)->find();
    if(empty($shop['id'])){
        $website=trim(config('website'));
        if(empty($shop['website'])){
            header("Location:http://www.".$website);
            exit;
        } 
        cmf_log('$shop[website]1'.$shop['website']);
        if($shop['website']==$website){
            $shop=$shop0;
        }else{
            $shop['website']=str_replace('.'.$website, '', $shop['website']);
            cmf_log('$shop[website]2'.$shop['website']);
            $shop=$m_shop->where('website',$shop['website'])->find();
        }
    }else{
        $shop=$m_shop->where('id',$shop['id'])->find();
    }
    
    if(empty($shop['name']) ){
        header("Location:http://www.".$website);
        exit;
    }
    switch ($shop['type']){
        case 0:
            $shop['aid']=1;
            break;
        case 1:
            $shop['aid']=$shop['id'];
            break;
        case 2:
            $tmp=$shop0;
            $tmp['aid']=1;
            $tmp['id']=$shop['id'];
            $tmp['rate']=$shop['rate'];
            $tmp['code']=$shop['code'];
            $tmp['website']=$shop['website'];
            $tmp['fpath']=$shop['fpath'];
            $shop=$tmp;
            break;
    } 
    session('shop',$shop);
      
}
/* 密码输入 */
function zz_psw($uid,$psw){
    $psw_count=config('psw_count');
    $m_user=Db::name('user');
    $user=$m_user->where('id',$uid)->find(); 
    if($user['user_pass']!=session('user.user_pass')){
        session('user',null);
        return [0,'密码已修改，请重新登录',url('user/login/login')];
    }
    //登录失败6次锁定
    if($user['psw_fail']>=$psw_count){
        return [0,'密码错误已达'.$psw_count.'次，请重新登录',url('user/login/login')];
    }
   
    if(cmf_compare_password($psw, $user['user_pass'])){
        $m_user->where('id',$uid)->update(['psw_fail'=>0]); 
        return [1];
    }else{
        $m_user->where('id',$uid)->setInc('psw_fail'); 
       
        return [0,'密码错误'.($user['psw_fail']+1).',连续'.$psw_count.'次将退出登录!',''];
    }
    
}
/* 发送微信信息 */
/*  cURL函数简单封装 */
function zz_curl($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
 
/* 过滤HTML得到纯文本 */
function zz_get_content($list,$len=100){
    //过滤富文本
    $tmp=[];
    foreach ($list as $k=>$v){
        
        $content_01 = $v["content"];//从数据库获取富文本content
        $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
        $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
        $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        $con = mb_substr($contents, 0, $len,"utf-8");//返回字符串中的前100字符串长度的字符
        $v['content']=$con.'...';
        $tmp[]=$v;
    }
    return $tmp;
}


/*制作缩略图 
 * zz_set_image(原图名,新图名,新宽度,新高度,缩放类型)
 *  */
function zz_set_image($pic,$pic_new,$width,$height,$thump=6){
    /* 缩略图相关常量定义 */
//     const THUMB_SCALING   = 1; //常量，标识缩略图等比例缩放类型
//     const THUMB_FILLED    = 2; //常量，标识缩略图缩放后填充类型
//     const THUMB_CENTER    = 3; //常量，标识缩略图居中裁剪类型
//     const THUMB_NORTHWEST = 4; //常量，标识缩略图左上角裁剪类型
//     const THUMB_SOUTHEAST = 5; //常量，标识缩略图右下角裁剪类型
//     const THUMB_FIXED     = 6; //常量，标识缩略图固定尺寸缩放类型
    //         $water=INDEXIMG.'water.png';//水印图片
    //         $image->thumb(800, 800,1)->water($water,1,50)->save($imgSrc);//生成缩略图、删除原图以及添加水印
    // 1; //常量，标识缩略图等比例缩放类型
    //         6; //常量，标识缩略图固定尺寸缩放类型
    $path=getcwd().'/upload/';
    //判断文件来源，已上传和未上传
    $imgSrc=(is_file($pic))?$pic:($path.$pic);
    
    $imgSrc1=$path.$pic_new;
    if(is_file($imgSrc)){
        $image = \think\Image::open($imgSrc); 
        $size=$image->size(); 
        if($size!=[$width,$height] || !is_file($imgSrc1)){ 
            $image->thumb($width, $height,$thump)->save($imgSrc1);
        } 
    } 
    return $pic_new; 
}
 
/* 组装图片 */
function zz_picid($pic,$pic_old,$type,$id){ 
    $path=getcwd().'/upload/';
    //logo处理
    if(!is_file($path.$pic)){
        return 0;
    } 
    //文件未改变
    if($pic==$pic_old){
        return $pic;
    }
    $size=config('pic_'.$type);
    $pic_new=$type.'/'.$id.'-'.time().'.jpg';
     
    $image = \think\Image::open($path.$pic); 
    $image->thumb($size['width'],  $size['height'],6)->save($path.$pic_new);
    
    unlink($path.$pic);
    if(is_file($path.$pic_old)){
        unlink($path.$pic_old);
    } 
    
    return $pic_new;
    
}
/* 为网址补加http:// */
function zz_link($link){
    //处理网址，补加http://
    $exp='/^(http|ftp|https):\/\//';
    if(preg_match($exp, $link)==0){
        $link='http://'.$link;
    }
    return $link;
}