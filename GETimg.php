<?php
/*
2013-4-28
All Code By MZ
call:632536
*/

include_once 'DB.php';
include_once 'class.curl.php';

class GETimg{
	
	function GETimg(){
		DB::Base("my","wx_");
		$this->CC=new CC();
	}
	
	private function cookies($_FILE){//检查cookies是否合格
		if(file_exists($_FILE)){
			$last_time=filemtime($_FILE);
			clearstatcache();
			$cookie_time=1*60*60; //设置cookies过期时间
			$re_time=time()-$last_time;
			if($re_time<$cookie_time){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	private function urlD($url, $A="", $D="DECODE"){//对传入字符串提取tooken
		if($D == "DECODE"){
			$parameter = explode('&',end(explode('?',$url)));
			foreach($parameter as $val){
				$tmp = explode('=',$val);
				$data[$tmp[0]] = $tmp[1];
			}
			return @$data;
		}else if($D == "ENCODE"){
			$getS = "";
			foreach ($A as $k => $v) {
				$getS .= "&".$k."=".$v;
			}
			$gs = substr($getS, 1);
			$retS = $url."?".$gs;
			return $retS;
		}
	}
	
	private function gCT($_U){//更新token和cookies
		$this->cookie_file=dirname(__FILE__) ."\data\cookies\cookies_".$_U['ID'].".txt"; //设置cookie保存路径
		
		if(!$this->cookies($this->cookie_file)){//cookies是否合格
			$Data = array(
				"cookies" => $this->cookie_file,
				"referer" => "https://mp.weixin.qq.com/"
			);
			$Post = array(
				"username"  =>  $_U['name'],
				"pwd"       =>  $_U['paw'],
				"imgcode"   =>  "",
				"f"         =>  "json"
			);

			$retStr = $this->CC->gCookies("https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN", $Data, $Post);
			$loginS = json_decode($retStr);

			if($loginS->Ret == "302"){//登陆成功
				$getini =  $this->urlD($loginS->ErrMsg);//对返回码进行截取
				$token  =  $getini['token'];
				if($token != $_U['token']){
					DB::UP("user", "token=$token", "ID=$_U[ID]");//更新token
				}
				$this->UM['ID'] = $_U['ID'];
				$this->UM['token'] = $token;
				$this->UM['fakeid'] = $_U['fakeid'];
				return $this->UM;
			}else{
				return false;
			}
		}else{//cookies合格
			$_UU = DB::SET("user", "ID=$_GET[group]");
			$this->UM['ID'] = $_UU[0]['ID'];
			$this->UM['token'] = $_UU[0]['token'];
			$this->UM['fakeid'] = $_U['fakeid'];
			return $this->UM;
		}
	}

	public function gImg($_D){//获取用户头像，返回头像数据流
		$CT = $this->gCT($_D);
		$url="https://mp.weixin.qq.com/cgi-bin/getheadimg?token=".$CT['token']."&fakeid=".$CT['fakeid'];
		$sendD = array(
			"cookies" => $this->cookie_file,
			"referer" => "https://mp.weixin.qq.com/",
		);
		$gImgS = $this->CC->gHtml($url, $sendD);
		if(!empty($gImgS)){
			$this->fileImg($CT, $gImgS);
		}else{
			return false;
		}
	}
	
	private function fileImg($_D, $img){//保存为本地图像
		if(@$img){
			$dir=$this->imgSrc.$_D['fakeid'].".jpg";
			$h = fopen($dir, 'wb' );
			fwrite( $h, $img ) ;
			fclose($h);
			$this->outimg($dir);
			return true;
		}else{
			return false;
		}
	}

	public function outimg($url){//二进制输出图像
		header('Content-Type:image/jpeg');
		$img = imagecreatefromjpeg($url);
		if($img != false){
			@imagejpeg($img);
			@imagedestroy($img);
			return true;
		}else{
			return false;
		}
	}
}


//fakeId : 一个用户的唯一ID，即使不同公众账号，fakeId也不会变
//如需更新用户头像，请到img/GETimg下删除相应的 fakeId.jpg

if(!empty($_GET['group']) && !empty($_GET['fakeId'])){

	$getimg=new GETimg();
	$getimg->imgSrc = "img/GETimg/";//定义头像储存位置

	if(file_exists($getimg->imgSrc.$_GET['fakeId'].".jpg")){
		$getimg->outimg($getimg->imgSrc.$_GET['fakeId'].".jpg");
	}else{
	    @$user=DB::SET("user","ID='$_GET[group]'");
	    @$user1=DB::SET("name","fakeId='$_GET[fakeId]'");
		if(@!empty($user[0]) && @!empty($user1[0])){//防止用户传入ID为不存在的用户
			$user[0]['fakeid']=$_GET['fakeId'];
			$getimg->gImg($user[0]);
		}else{
			return false;
		}
	}
}
?>