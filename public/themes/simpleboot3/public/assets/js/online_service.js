$(function(){
	var str="<div class='duifang'><span class='touxiang'><img src='./img/wang.jpg' alt=''></span><span class='xiaobiao'></span><p class='text_s'>请问有什么可以帮助您的?</p></div>";
	setTimeout(function(){
		$(".xiaoxi").append(str);
	},1000);
	$(".line_send>a").click(function(){
		var txts=$(".line_input>input").val();
		if(!txts==""){
			str=`<div class="jifang">
				<span class="touxiang">
				<img src="./img/kehu.jpg" alt="">
			</span>
				<span class="xiaobiao"></span>
				<p class="text_s">
					${txts}
				</p>
			</div>`
			$(".xiaoxi").append(str);
			var height=$(".xiaoxi").height();
			$("html,body").animate({
				scrollTop:`${height}px`,
			},500);
			$(".line_input>input").val('');
		}
	});
});