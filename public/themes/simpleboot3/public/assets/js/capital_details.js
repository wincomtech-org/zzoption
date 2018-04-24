$(function(){
	$(".nav_ssy>ol>li").click(function(){
		var index=$(this).index();
		$(this).addClass("red_ss").siblings().removeClass("red_ss");
		$($(".show_zong>div")[index]).css("display","block").siblings().css("display","none");
		sessionStorage.removeItem("gggy");
		sessionStorage.setItem("gggy",index);
	});
});