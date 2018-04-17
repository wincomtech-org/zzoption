$(function(){
	// $(".footer>ol>a:nth-child(3)").click(function(){
	// 	$("html,body").animate({
	// 		scrollTop:"0"
	// 	},500);
	// });
	var sss_s=sessionStorage.getItem("zixun");
	$($(".fun_qh>ol>li")[sss_s]).addClass("red_text").siblings().removeClass("red_text");
	$($(".show>div")[sss_s]).css("display","block").siblings().css("display","none");
	$(".fun_qh>ol>li").click(function(){
		var index_s=$(this).index();
		sessionStorage.setItem("zixun",index_s);
		// $(this).addClass("red_text").siblings().removeClass("red_text");
	});
	var height_s=sessionStorage.getItem("bug");
	var h=0;
	$(window).scrollTop(height_s);
	$(window).scroll(function(){
		h=$(this).scrollTop();
		sessionStorage.setItem("bug",h);
	});
});