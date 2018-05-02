var a=$(window).width();
$("html").css("font-size",a/7.5+"px");
$(window).resize(function(){
    var a=$(window).width();
    $("html").css("font-size",a/7.5+"px");
});
function msg(text,html='',url=''){
    var str='<div class="show_box"><div class="show_box_s"><p class="text_p1">个股期权温馨提示</p><p class="text_p2">'+text+'</p><button class="btn_ght">确认</button></div></div>'
    $("body").append(str);
	var timer_s=setTimeout(function(){
		$(".btn_ght").click();
	},6000);
	$(".btn_ght").click(function(){
		clearInterval(timer_s);
		$(".show_box").fadeOut().remove();
		if(url!=''){
			self.location=url;
		}
	});
	if(html!=''){
		$('body').append(html);
	}
}
function button_click(obj,action=1,text='',type=1){
    if(action==1){
            obj.prop('disabled','disabled');
            if(text==''){
                text='正在提交';
            }
    }else{
            obj.prop('disabled',false);
            if(text==''){
                text='提交';
            }
    } 
    if(type=1){
            obj.text(text);
    }else{
            obj.val(text);
    }
    
}
//短信计时函数
function down_s(element,fun){
    element.off();
//    var txt_h=element.html();
    var num_s=60;
    element.html(num_s);
    var timer=setInterval(function(){
        num_s--;
        if(num_s<0){
            element.on("click",fun);
            element.html("重新发送");
            clearInterval(timer);
        }else{
            element.html(num_s);
        }
    },1000);
}
function is_mobile(str){
    var reg=/^[1][3,4,5,6,7,8][0-9]{9}$/; 
    return reg.test(str);
}
function is_password(str){
    var reg= /^[a-zA-Z0-9]{6,20}$/;
    return reg.test(str);
}
function is_bankCard(str){
    var reg=/^\d{15}|\d{19}$/;
    return reg.test(str);
}
function is_username(str){
        
    var reg=/^[\u4E00-\u9FA5]{2,10}$/;
    return reg.test(str);
}
//18位|15位
function is_idcard(str){
    var reg=/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
    return reg.test(str);
}
function isSms(str){
    var reg=/^\d{4}$/;
    return reg.test(str);
}
        