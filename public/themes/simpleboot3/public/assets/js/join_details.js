$(function(){
	$(".text_area>textarea").val("")
//	实现上传照片预览功能
	$("#filed").change(function(){
		console.log(this.files[0]);
		var leixing=this.files[0].type.split("/")[0];
		var daxiao=this.files[0].size/1000;
		var url = null;  
            if (window.createObjcectURL != undefined) {  
                url = window.createOjcectURL(this.files[0]);  
            } else if (window.URL != undefined) {  
                url = window.URL.createObjectURL(this.files[0]);  
            } else if (window.webkitURL != undefined) {  
                url = window.webkitURL.createObjectURL(this.files[0]);  
            }  
		console.log(url);
		if(leixing==="image"){
			//为图片格式
		}else{
			//不是图片格式
			$(".xinxi>label").html("请不要上传非照片格式文件！").css("color","red");
			return;
		}
//		if(daxiao>2048){
//			$(".xinxi>label").html("请上传小于2M的图片！").css("color","red");
//			return;
//		}
		$(".shangchuan>label>img").attr("src",url).css("display","block");
	});
	$(".guyuan_sty>p").click(function(){
		$(this).addClass("red_sssu").siblings("p").removeClass("red_sssu");
	});
});