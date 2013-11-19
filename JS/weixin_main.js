// JavaScript Document date:2013-5-11
function wx(){
	var T=this;
	this.LR="R";
	this.i=0;
	this.Len=0;
	this.last_ID=0;
	this.first=true;
	this._setTime=null;
	this.msgID=0;
	
	this.star = function (){
		$body_H = window.screen.availHeight;
		$title_H = $('.weixin_title').height();
		$('#wx_content').height($body_H-$title_H);
		return $body_H-$title_H;
	}

	this.show=function (){
		$con_H=$('#wx_msg').height();
		$last_H=$('.wx_user_box:last').outerHeight(true);
		$h = 0;
		for (var i = T.msgID; i >= 0; i--) {
			$h += $('.wx_user_box').eq(i).outerHeight(true);
			if($h > 306){
				$('#wx_msg').animate({marginTop:"-="+$last_H+"px"},800);
				break;
			}
		};
	}
	
	this._msg=function (msg){
		T.show();
		T.test(msg);
	}

	this.socket = function (){
	    var url='ws://mz.hdletgo.com:8000';
	    socket=new WebSocket(url);
	    socket.onmessage=function(msg){
	    	eval('var sData='+msg.data);
	    	if(sData.ID == ID){
	    		T._msg(sData.data);
	    	}
	    }
	}
	
	this.test=function (_msg){
		var box=document.createElement("div");
		var head=document.createElement("div");
		var txt_box=document.createElement("div");
		var txt=document.createElement("div");
		var t=document.createElement("div");
		var sen=document.createElement("div");
		var bor=document.createElement("div");
		
		box.className="wx_user_box wx_user_box_"+T.LR;
		box.setAttribute("msg-id",T.msgID);
		box.style.opacity=0;
		$(box).delay(300).animate({opacity:1},400);
		head.className="wx_user_head_box_"+T.LR;
		head.innerHTML="<div class='wx_user_head'> <img src='http://wx.xingkong.us/GETimg.php?group="+ID+"&fakeId="+_msg['fakeId']+"' /></div>";
		txt_box.className="wx_user_txt_"+T.LR+"_box";
		txt.className="wx_user_txt_"+T.LR;
		t.className="wx_user_T wx_user_T_"+T.LR;
		if(_msg['super']>0){
			$name="<a style='color:#f00'>"+_msg['name'].replace(/\\/g,"")+"：</a>";
		}else{
			$name=_msg['name'].replace(/\\/g,"")+"：";
		}
		t.innerHTML=$name+_msg['msg'].replace(/\\/g,"");
		sen.className="wx_user_sen wx_user_sen_"+T.LR;
		bor.className="wx_user_sen wx_user_bor_"+T.LR;
		txt.appendChild(t);
		txt.appendChild(sen);
		txt.appendChild(bor);
		if(T.LR=="L"){
			txt_box.appendChild(head);
			txt_box.appendChild(txt);
			T.LR="R";
		}else if(T.LR=="R"){
			txt_box.appendChild(txt);
			txt_box.appendChild(head);
			T.LR="L";
		}
		T.msgID++;
		box.appendChild(txt_box);
		document.getElementById("wx_msg").appendChild(box);
	}
}
var WX=new wx();
WX.maxH = WX.star();
WX.socket();