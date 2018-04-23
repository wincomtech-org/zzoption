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
    
}
/* 批量发送消息 */
function zz_msgs($data){
    //先保存消息内容再保存用户消息连接
    $data_txt=[
        'aid'=>$data['aid'],
        'title'=>$data['title'],
        'content'=>$data['content'],
        'type'=>$data['type'],
        'time'=>time(),
    ];
    $msg_id=Db::name('msg_txt')->insertGetId($data_txt);
    foreach($data['order'] as $k=>$v){
        
    }
    $data_msg=[
        'msg_id'=>$msg_id,
        'uid'=>$data['uid']
    ];
    Db::name('msg')->insert($data_msg);
    
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
 

/* 为网址补加http:// */
function zz_link($link){
    //处理网址，补加http://
    $exp='/^(http|ftp|https):\/\/([\w.]+\/?)\S*/';
    if(preg_match($exp, $link)==0){
        $link='http://'.$link;
    }
    return $link;
}