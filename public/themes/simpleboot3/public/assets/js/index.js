$(function(){
	sessionStorage.removeItem("href_s");
	sessionStorage.setItem("href_s","./index.html");
//切换页面再回到首页滚动条回到上次位置，增加用户体验！
	var num_s=0;
	var height_s=sessionStorage.getItem("hei");
	$(window).scrollTop(height_s);
	$(window).scroll(function(){
		num_s=$(window).scrollTop();
		sessionStorage.removeItem("hei");
		sessionStorage.setItem("hei",num_s);
	});
	// //在首页点击foorter的首页回到顶部
	// $(".footer>ol>a:nth-child(1)").click(function(){
	// 	$("html,body").animate({
	// 		scrollTop:"0"
	// 	},500);
	// });
	//点击询价删除参数
	$(".func>ol>a:nth-child(1)").click(function(){
		sessionStorage.removeItem("key");
	});
	//轮播开始
	//=================
	(function(){
		var secend=4000;
		var animating=400;
		var a="<li>"+$(".body_s>.imgs>ul>li:first-child").html()+"</li>";
		$(".body_s>.imgs>ul").append(a);
		$(".body_s>.imgs>ul>li:first-child")
		var s=$(".body_s>.imgs>ul>li").length;
		var wih_s=$(window).width();
		var uw=s*wih_s+100;
		$(".body_s>.imgs>ul>li").css("display","block");
		function auto_s(){
			wih_s=$(window).width();
			uw=s*wih_s+100;
			$(".body_s>.imgs").width(wih_s);
			$(".body_s>.imgs>ul>li>a>img").width(wih_s);
			$(".body_s>.imgs>ul").width(uw);
			console.log(wih_s);
		}
		
		auto_s();
		$(window).resize(function(){
			auto_s();
		});
		var htm_s="";
		for(var i=1;i<s;i++){
			htm_s+="<li></li>";
		}
		htm_s="<ol>"+htm_s+"</ol>";
		$(".body_s>.imgs").append(htm_s);
		$(".body_s>.imgs>ol>li:first-child").addClass("lis");
		var marginL=0;
		var a=0;
//		console.log(wih_s*(s-2))
		function autoplay(){
			a++;
			if(a>(s-2)){a=0}
			$($(".body_s>.imgs>ol>li")[a]).addClass("lis").siblings().removeClass("lis");
			marginL+=wih_s;
//			console.log(marginL);
			if(marginL>wih_s*(s-1)){
				$(".body_s>.imgs>ul").animate({
					marginLeft:"0px",
				},0);
				marginL=wih_s;
			}
			$(".body_s>.imgs>ul").animate({
					marginLeft:"-"+marginL+"px",
				},animating);
		}
		var timer=setInterval(autoplay,secend);
		$(".body_s>.imgs>ol>li").click(function(){
			$(".body_s>.imgs>ul").stop();
			clearInterval(timer);
			a=$(this).index();
			$(this).addClass("lis").siblings().removeClass("lis");
			marginL=$(this).index()*wih_s;
			$(".body_s>.imgs>ul").animate({
					marginLeft:"-"+marginL+"px",
				},animating);
			timer=setInterval(autoplay,secend);	
		});
			
	})();
	//询价轮播开始
	//==============
	var bs="<div>"+$(".body_s>.xydan>div>div:nth-child(2)>div>div:first-child").html()+"</div>"
//	console.log(bs);
	$(".body_s>.xydan>div>div:nth-child(2)>div").append(bs);
	var dheig=$(".body_s>.xydan>div>div:nth-child(2)>div").height();
	var xheigs=$(".body_s>.xydan>div>div:nth-child(2)>div>div").height();
//	console.log(dheig,xheigs);
	var marginT=xheigs;
	setInterval(function(){
		marginT+=xheigs;
		if(marginT>dheig-xheigs){
			$(".body_s>.xydan>div>div:nth-child(2)>div").css("margin-top","0");
			marginT=xheigs;
		}
		$(".body_s>.xydan>div>div:nth-child(2)>div").animate({
			marginTop:"-"+marginT+"px",
		},500);
	},2000);
		
	
	//==============
	//询价轮播结束
	//=================
	//轮播结束
});
