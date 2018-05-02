$(function(){
	//轮播开始
	//=================
	(function(){
		var secend=4000;
		var animating=400;
		var a="<li>"+$(".body_s>.imgs>ul>li:first-child").html()+"</li>";
        var sg=typeof($(".body_s>.imgs>ul>li:first-child").html())==="undefined";
        var sh=$(".body_s>.imgs>ul>li").length<2;
//        console.log(sh);
		if(!sg && !sh ){
            $(".body_s>.imgs>ul").append(a);
            setgfd();
        }
        
        function setgfd(){
//           console.log(23);
//            $(".body_s>.imgs>ul>li:first-child");
            var s=$(".body_s>.imgs>ul>li").length;
            var wih_s=$(window).width();
//             console.log(s);
            var uw=s*wih_s+100;
            $(".body_s>.imgs>ul>li").css("display","block");
            function auto_s(){
                wih_s=$(window).width();
                uw=s*wih_s+100;
                $(".body_s>.imgs").width(wih_s);
                $(".body_s>.imgs>ul>li>a>img").width(wih_s);
                $(".body_s>.imgs>ul").width(uw);
//                console.log(wih_s);
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
        }
	})();
	//询价轮播开始
	//==============
	var bs="<div>"+$(".body_s>.xydan>div>div:nth-child(2)>div>div:first-child").html()+"</div>"
    if(!($(".body_s>.xydan>div>div:nth-child(2)>div>div:first-child").html()==undefined)){
        $(".body_s>.xydan>div>div:nth-child(2)>div").append(bs);
        fgt_ds();
    }else{
        $(".body_s>.xydan>div>div:nth-child(2)>div").append("暂无数据。。。。。。。");
    }
    
	function fgt_ds(){
        var dheig=$(".body_s>.xydan>div>div:nth-child(2)>div").height();
        var xheigs=$(".body_s>.xydan>div>div:nth-child(2)>div>div").height();
        var marginT=xheigs;
        setInterval(function(){
            if(marginT>dheig-xheigs){
                $(".body_s>.xydan>div>div:nth-child(2)>div").css("margin-top","0");
                marginT=xheigs;
            }
            $(".body_s>.xydan>div>div:nth-child(2)>div").animate({
                marginTop:"-"+marginT+"px",
            },500);
            marginT+=xheigs;
        },2000);
     }
	//==============
	//询价轮播结束
	//=================
	//轮播结束
});
