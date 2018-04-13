$(function(){
	var s=sessionStorage.getItem("scroll");
	var height_s;
	if(s==null){s=0}{
		$(window).scrollTop(s);
	}
	$(window).scroll(function(){
		height_s=$(this).scrollTop();
		sessionStorage.removeItem("scroll");
		sessionStorage.setItem("scroll",height_s);
	});
	var b=sessionStorage.getItem("gggy");
	if(b==null){b=0}{
		$($(".nav_ssy>ol>li")[b]).addClass("red_ss").siblings().removeClass("red_ss");
		$($(".show_zong>div")[b]).css("display","block").siblings().css("display","none");
	}
	$(".nav_ssy>ol>li").click(function(){
		var index=$(this).index();
		$(this).addClass("red_ss").siblings().removeClass("red_ss");
		$($(".show_zong>div")[index]).css("display","block").siblings().css("display","none");
		sessionStorage.removeItem("gggy");
		sessionStorage.setItem("gggy",index);
	});
});