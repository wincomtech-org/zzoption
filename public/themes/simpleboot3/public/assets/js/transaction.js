$(function(){
	//切换页面
	// var index_s=sessionStorage.getItem("key");
	(function(){
		// if(index_s===null){index_s=0}
		// console.log(index_s);
		// $($(".body_s>.show_g>div")[index_s]).css("display","block").siblings().css("display","none");
		// $($(".body_s>ul>li")[index_s]).addClass("bh_s").siblings().removeClass("bh_s");
		// var a=0;
		$(".body_s>ul>li").click(function(){
			a=$(this).index();
			sessionStorage.removeItem("key");
			sessionStorage.setItem("key",a);
			$($(".body_s>ul>li")[a]).addClass("bh_s").siblings().removeClass("bh_s");
			$($(".body_s>.show_g>div")[a]).css("display","block").siblings().css("display","none");
		});
	})();
	//名义本金
	var principal=$(".principal>.red").children("span").html();
	(function(){
		$(".principal>li").click(function(){
			principal=$(this).children().html();
			var a=$(this).index();
			console.log(principal,cycle);
			$($(".principal>li")[a]).addClass("red").siblings().removeClass("red");
		});
	})();
	//行权周期
	var cycle=$(".cycle>.red").children("span").html();
	(function(){
		$(".cycle>li").click(function(){
			cycle=$(this).children().html();
			var a=$(this).index();
			console.log(principal,cycle);
			$($(".cycle>li")[a]).addClass("red").siblings().removeClass("red");
		});
	})();
	console.log(principal,cycle);
	//同意阅读项
	(function(){
		var a=0;
		$("#label_s").click(function(){
			a++;
			if(a%2===1){
				$(this).addClass("spans");
				$(".tijiao").addClass("tijiao_s");
			}else{
				$(this).removeClass("spans");
				$(".tijiao").removeClass("tijiao_s");
			}
			
		});
		$(".label_s").click(function(){
			$("#label_s").click();
		})
	})();
	(function(){
		$(".sub_s_h>span:last-child").click(function(){
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
		$("sub_ss_jk>span:last-child").click(function(){
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
	var text_i;
	$(".input1").bind('input porpertychange',function(){
		$(".botton_input").fadeIn();
		text_i=$(this).val();
		console.log(text_i);
	});
	$(".botton_input>p").click(function(){
		$(".botton_input").css("display","none");
		$(".input1").val($(this).html());
		text_i=$(this).html();
		console.log(text_i);
	});
	$element = $('.botton_input');
	$(document).on('click', function(){
        $element.css("display","none");
    }).on('click', '.need-hidden-element', function(event){
        event.stopPropagation();
    })
});