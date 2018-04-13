$(function(){
	//切换页面
	var index_s=sessionStorage.getItem("key");
	(function(){
		if(index_s===null){index_s=0}
		console.log(index_s);
		$($(".body_s>.show_g>div")[index_s]).css("display","block").siblings().css("display","none");
		$($(".body_s>ul>li")[index_s]).addClass("bh_s").siblings().removeClass("bh_s");
		var a=0;
		$(".body_s>ul>li").click(function(){
			a=$(this).index();
			sessionStorage.removeItem("key");
			sessionStorage.setItem("key",a);
			$($(".body_s>ul>li")[a]).addClass("bh_s").siblings().removeClass("bh_s");
			$($(".body_s>.show_g>div")[a]).css("display","block").siblings().css("display","none");
		});
	})();
	//名义本金
	(function(){
		$(".body_s>.show_g>.shows_1>div:nth-child(3)>ol>li").click(function(){
			var a=$(this).index();
			$($(".body_s>.show_g>.shows_1>div:nth-child(3)>ol>li")[a]).addClass("red").siblings().removeClass("red");
		});
	})();
	//行权周期
	(function(){
		$(".body_s>.show_g>.shows_1>div:nth-child(4)>ol>li").click(function(){
			var a=$(this).index();
			$($(".body_s>.show_g>.shows_1>div:nth-child(4)>ol>li")[a]).addClass("red").siblings().removeClass("red");
		});
	})();
	//同意阅读项
	(function(){
		var a=0;
		$(".body_s>.show_g>.shows_1>div:nth-child(5)>span").click(function(){
			a++;
			if(a%2===1){
				$(this).addClass("spans");
				$(".body_s>.show_g>.shows_1>div:nth-child(6)>.tijiao").addClass("tijiao_s");
			}else{
				$(this).removeClass("spans");
				$(".body_s>.show_g>.shows_1>div:nth-child(6)>.tijiao").removeClass("tijiao_s");
			}
			
		});
	})();
	(function(){
		$(".body_s>.show_g>.shows_2>div:nth-child(2)>div>span:last-child").click(function(){
			if($(this).hasClass("sp_red")){
				var a=$(this).parent().children("span:first-child()").html();
				$($(".body_s>.show_g>div")[0]).css("display","block").siblings().css("display","none");
				$($(".body_s>ul>li")[0]).addClass("bh_s").siblings().removeClass("bh_s");
				$(".shows_1>div>.input1").val(a);
				sessionStorage.removeItem("key");
			}
		});
	})();
	(function(){
		$(".body_s>.show_g>.shows_4>div:nth-child(2)>div>span:last-child").click(function(){
			var b=$(this).parent().children("span:first-child()").html();
			$($(".body_s>.show_g>div")[0]).css("display","block").siblings().css("display","none");
			$($(".body_s>ul>li")[0]).addClass("bh_s").siblings().removeClass("bh_s");
			$(".shows_1>div>.input1").val(b);
			sessionStorage.removeItem("key");
		});
	})();
	var height_s=sessionStorage.getItem("but");
	var h=0;
	$(window).scrollTop(height_s);
	$(window).scroll(function(){
		h=$(this).scrollTop();
		sessionStorage.setItem("but",h);
	});
});