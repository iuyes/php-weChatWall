<?php
/**
* @author    MAXming
* @call      18825142536
* @version   2013-10-25
*/

include_once 'DB.php';
include_once 'class.curl.php';
include_once 'qq-emoji.php';
include_once 'php.websocket.php';

DB::Base("my","wx_");


class callback{

	public function Token($Token){
        $echoStr = $_GET["echostr"];
        if($this->check($Token)){
        	echo $echoStr;
        	exit;
        }
	}
	
	private function check($token){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
		
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

class WX{
	function WX(){
		$this->_time=$date=date('Y-m-d H:i:s',time());
		$this->CC = new CC();//curl对象赋值给变量
	}

	private function DEXML($str){//对XML进行解码
		$xmlObj = simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
		return $xmlObj;
	}
	
	private function urlD($url, $A="", $D="DECODE"){//对传入字符串提取tooken
		if($D == "DECODE"){
			$parameter = explode('&',end(explode('?',$url)));
			foreach($parameter as $val){
				$tmp = explode('=',$val);
				$data[$tmp[0]] = trim($tmp[1]);
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

	public function login($_U){//登陆

		//cookies保存路径
		$this->cookie_file = dirname(__FILE__) ."/data/cookies/cookies_".$_U['ID'].".txt";

		if(!$this->cookies($this->cookie_file) || empty($_U['token'])){//cookies是否合格，是否已有token
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
					DB::UP("user", "token='$token'", "ID=$_U[ID]");//更新token
				}
				$this->UM['ID']    = $_U['ID'];
				$this->UM['token'] = $token;
				$this->CData();
				return $this->UM;
			}else{
				return false;
			}
		}else{//cookies和token都合格
			$this->UM['ID']    = $_U['ID'];
			$this->UM['token'] = $_U['token'];
			$this->CData();
			return $this->UM;
		}
	}

	public function DEname($time, $Str){//***匹配FromUserName与nickName和fakeid对应
		foreach($Str as $user){
			if($time == $user->date_time){
				return $user;
				break;
			}
		}
	}

	private function newMsgNum($D){//获取lastID后消息数目，可用来检测cookies合法性
		$url = "https://mp.weixin.qq.com/cgi-bin/getnewmsgnum";

		$_D = array(
			"cookies" => $D['cookies'],
			"referer" => "https://mp.weixin.qq.com/",
		);

		$_P = array(
			"token"      => $D['token'],
			"lang"       => "zh_CN",
			"t"          => "ajax-getmsgnum",
			"lastmsgid"  => !empty($D['lastID']) ? $D['lastID'] : 0,
		);
		$retS = $this->CC->postMsg($url, $_D, $_P);
		$retA = json_decode($retS);
		if($retA['ret'] == "0"){
			return $retA['newTotalMsgCount'];
		}else{
			return false;
		}
	}

	private function isUser($U){//判断用户是否已入库，并输出用户信息

		$isU = DB::SET("name", "FromUserName='$U[FromName]'");
		if(!empty($isU[0]['ID'])){
			$retS['ID']            =  $isU[0]['ID'];
			$retS['super']         =  $isU[0]['super'];
			$retS['fakeid']        =  $isU[0]['fakeId'];
			$retS['name']          =  $isU[0]['UserName'];
			$retS['deName']        =  $isU[0]['deName'];
			$retS['remarkName']    =  @$isU[0]['remarkName'];
			return $retS;
		}else{//用户入库

	    	$msgL = $this->msgList($U);                           //获取消息页面
			$retInfo = $this->DEname($U['CreateTime'], $msgL);    //FromUserName 与 UserName, fakeId 等匹配
			if(!empty($retInfo->fakeid)){
				$deName = qqEmoji::Str($retInfo->nick_name, "DECODE");
				$retS['super']         =  0;
				$retS['fakeid']        =  $retInfo->fakeid;
				$retS['name']          =  $retInfo->nick_name;
				$retS['deName']        =  $deName;
				$retS['remarkName']    =  @$retInfo->remark_name;
				$time                  =  time();
				DB::IN("name", "UserGroup, fakeId, FromUserName, UserName, deName, remarkName, date, last_date", "'$U[ID]', '$retS[fakeid]', '$U[FromName]', '$retS[name]', '$deName', '$retS[remarkName]', '$time', '0'");
				$retS['ID']            =  mysql_insert_id();
				return $retS;
			}else{
				return false;
			}
		}
	}

	private function msgList($_D){//获取消息列表
		$urlD = array(
			"t"       =>  "message/list",
			"count"   =>  "10", //一页获取多少条消息
			"day"     =>  "0",  //获取前N天消息
			"token"   =>  $_D['token'],
			"lang"    =>  "zh_CN"
		);
		$url = $this->urlD("https://mp.weixin.qq.com/cgi-bin/message", $urlD, "ENCODE");

		$D=array(
			"cookies" => $this->cookie_file,
			"referer" => 'https://mp.weixin.qq.com/',
		);
		$retHtml=$this->CC->gHtml($url, $D);
		preg_match_all( '/{\"msg_item\":([\w\W]*)\}\).msg_item/iU', $retHtml, $retGet);
		return json_decode($retGet[1][0]);
	}

	public function CData(){
		$WXD = $this->DEXML($GLOBALS["HTTP_RAW_POST_DATA"]);
		$D = array(
			"MsgType"       =>   $WXD->MsgType,
			"FromUserName"  =>   $WXD->FromUserName,
			"ToUserName"    =>   $WXD->ToUserName,
			"CreateTime"    =>   $WXD->CreateTime,
			"Content"       =>   qqEmoji::Str($WXD->Content, "DECODE"), //对字符串进行解码
		);
		if($D['MsgType'] == "event"){//关注事件

		    $S['type'] = "text";
		    $S['Content'] = "欢迎关注,"."\r\n"."max哲"."\r\n"."对着这里发消息就可以上墙了"."\r\n"."上墙规则："."\r\n"."① 在140字以内"."\r\n"."② 不许发敏感词汇"."\r\n"."③ 发送频率为1分钟一次"."\r\n"."微博墙地址：http://wx.xingkong.us";
			$this->sendMsg($S, $D);

		}else if($D['MsgType'] == "text"){

			$userD = array(
				"ID"          =>  $this->UM['ID'],
				"type"        =>  $D['MsgType'],
				"token"    	  =>  $this->UM['token'],
				"FromName"    =>  $D['FromUserName'],
				"Content"     =>  $D['Content'],
				"CreateTime"  =>  $D['CreateTime'],
			);
			$Uinfo = $this->isUser($userD);

			$name  = !empty($Uinfo['remarkName']) ? $Uinfo['remarkName'] : $Uinfo['deName'];
			if($this->dbtalk($Uinfo, $userD)){
				if(!empty($Uinfo)){
					$S['type'] = "text";
				    $S['Content'] = $Uinfo['name']."\r\n"."您的消息已上墙"."\r\n"."发送频率为1分钟一次"."\r\n"."微博墙地址：http://wx.xingkong.us";
				}
				$this->socketView(array("ID" => $userD['ID'], "fakeId" => $Uinfo['fakeid'], "name" => $name, "super" => $Uinfo['super'], "msg" => $userD['Content']));
			}else{
				if(!empty($Uinfo)){
					$S['type'] = "text";
				    $S['Content'] = $Uinfo['name']."\r\n"."不要贪心喔~"."\r\n"."发送频率为1分钟一次！"."\r\n"."微博墙地址：http://wx.xingkong.us";
				}
			}
			$this->sendMsg($S, $D);

		}
	}

	private function socketView($_D){
		$WebSocketClient = new WebsocketClient('mz.hdletgo.com', 8000);
		$WebSocketClient->sendData('type=ltiao&ID='.$_D['ID'].'&fakeId='.$_D['fakeId'].'&name='.$_D['name'].'&super='.$_D['super'].'&msg='.$_D['msg'].'&key=all');
		unset($WebSocketClient);
	}
	private function dbtalk($U, $_D){
		$time       = time();
		$_tmp       = DB::SET("name","ID='$U[ID]'");
		$last_time  = $_tmp[0]['last_date'];
		$tmp_time   = $time-$last_time;

		if($tmp_time >= 1*60){  //1分钟发一次
			DB::UP("name", "last_date='$time', MsgNum=MsgNum+1", "ID='$U[ID]'");
			DB::IN("txt", "UserGroup, UserID, Type, text, date", "'$_D[ID]', '$U[ID]', '$_D[type]', '$_D[Content]', '$time'");
			return true;
		}else{
			return false;
		}
	}

	public function sendMsg($_S, $_D){
		if($_S['type'] == "text"){
			$text = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						</xml>";
			$resultStr = sprintf($text, $_D['FromUserName'], $_D['ToUserName'], time(), $_S['type'], $_S['Content']); 	
			echo $resultStr;
			return true;
		}
	}
}

if(!empty($GLOBALS["HTTP_RAW_POST_DATA"])){
	
	$WX = new WX();
	if(!empty($_GET['ID'])){
		$user = DB::SET("user", "ID=$_GET[ID]");
		$U['ID']    =  $user[0]['ID'];
		$U['token'] =  $user[0]['token'];
		$U['name']  =  $user[0]['user'];
		$U['paw']   =  $user[0]['paw'];

		$WX -> login($U);
	}else{
		return false;
	}

}else{//初次认证
	if(!empty($_GET['ID'])){//ID用户
		
		$CB=new callback();
		$user = DB::SET("user", "ID=$_GET[ID]");
		if(!empty($user[0]['ID'])){

			@$CB->Token($user[0]['TokenTXT']);
		}else{
			return false;
		}

	}else{
		return false;
	}
}
?>