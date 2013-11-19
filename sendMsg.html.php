<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>消息推送</title>
</head>

<body>
<?php
/*
2013-4-28
All Code By MZ
call:632536
*/
include_once 'DB.php';
DB::Base("my","wx_");

//@ob_implicit_flush(true);//输出缓冲
//@ob_end_clean();
@set_time_limit(0);//设置页面最久执行时间
//@echo str_pad(" ", 256); 

class sM{
	
	private function _token($url){
		$parameter = explode('&',end(explode('?',$url)));
		foreach($parameter as $val){
			$tmp = explode('=',$val);
			$data[$tmp[0]] = $tmp[1];
		}
		return @$data;
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
	
	private function _login($user){
		$is_user=DB::SET("user","user='$user[name]'");
		if(!@$is_user[0]){
			$is_ID=DB::COUNT("max(ID)","user","");
			if(!@$is_ID['max(ID)']){
				$is_ID['max(ID)']=0;
			}
			$_user['ID']=$is_ID['max(ID)']+1;
		}else{
			$_user['token']=@$is_user[0]['token'];
			$_user['ID']=@$is_user[0]['ID'];
		}
		
		$this->cookie_file=dirname(__FILE__) ."/data/cookies_".$_user['ID'].".txt"; //设置cookie保存路径
		
		if(!$is_user[0] || !$this->cookies($this->cookie_file) || $is_user[0]['token']==0 ){//login
			$login_url="https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
			$post_fields['username'] = $user['name'];
			$post_fields['pwd'] = $user['paw'];
			$post_fields['imgcode'] = "";
			$post_fields['f'] = 'json';
			print_r($post_fields);
			$ch = curl_init($login_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_REFERER, "https://mp.weixin.qq.com/");
			curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);
			curl_setopt($ch, CURLOPT_COOKIEJAR,$this->cookie_file);
			$getini=json_decode(curl_exec($ch));
			curl_close($ch);
			print_r($getini);
			if($getini->Ret=="302"){
				@$getini=$this->_token($getini->ErrMsg);
				@$token=@$getini['token'];
				if(@!empty($token)){
				    DB::UP("user","token='$token'","user='$user[name]'");
				}else{
					return false;
				}
		    }else{
		    	return false;
		    }
		}else{
			$token=$_user['token'];
		}
		$this->loginMsg['token']=$token;
		$this->loginMsg['ID']=$_user['ID'];
		return true;
	}

	private function getWX($_token,$_url){//获取微信页面
		if(@!empty($_token)){
			$chs = curl_init();
			curl_setopt($chs, CURLOPT_URL, $_url);
			curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($chs, CURLOPT_SSL_VERIFYPEER,FALSE);
		    curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($chs, CURLOPT_COOKIEFILE,$this->cookie_file);
			curl_setopt($chs, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0");
			$html=curl_exec($chs);
			curl_close($chs);
			return $html;
		}else{
			return false;
		}
	}

	private function dbFriend($_G,$_U){//所有用户入库
		$tmp['fakeId']=$_U->id;
		$tmp['nickName']=$_U->nick_name;
		$tmp['remarkName']=$_U->remark_name;
		$is_fakename=DB::SET("fakename","userGroup='$_G' AND fakeId='$tmp[fakeId]'");
		if(!$is_fakename[0]){
		    //$this->userInfo($this->loginMsg);
		    DB::IN("fakename","userGroup,fakeId,nickName,remarkName","'$_G','$tmp[fakeId]','$tmp[nickName]','$tmp[remarkName]'");
		    return true;
		}
	}
	
	private function postWxMsg($_D,$_P){//post 数据
		$chs = curl_init();
		curl_setopt($chs, CURLOPT_URL, $_D['url']);  
		curl_setopt($chs, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chs, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0");
		curl_setopt($chs, CURLOPT_REFERER,$_D['referer']);
		curl_setopt($chs, CURLOPT_POST, 1);
		curl_setopt($chs, CURLOPT_POSTFIELDS,$_P);
		curl_setopt($chs, CURLOPT_COOKIEFILE,$this->cookie_file);
		$html=json_decode(curl_exec($chs));
		curl_close($chs);
		if(@!empty($html)){
			return $html;
		}else{
			return false;
		}
	}
	
	private function initiativeSendMsg($_D,$_T){//主动推送
		$sendC=array(
		    "url"=>'https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response',
			"referer"=>'https://mp.weixin.qq.com/cgi-bin/getmessage?t=wxm-message&token='.$_D['token'].'&lang=zh_CN&count=50',
			"toFakeid"=>$_D['fakeId']
		);
		
		$sendP=array(
		    "tofakeid"=>'458935',//67263985$_D['fakeId']
		    "type"=>1,
		    "error"=>'false',
		    "content"=>$_T,
		    "token"=>$_D['token'],
		    "ajax"=>1,
		    "quickreplyid"=>'100000999',
		);
		
		$retObj=$this->postWxMsg($sendC,$sendP);
		if($retObj->ret=="0"){
			echo $_D['fakeId']."-->OK";
			echo "\n";
			echo "\n";
		}
	}
	
	private function userInfo($_D){//获取用户信息
		$sendC=array(
			"url"=>'https://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&fakeid='.$_D['fakeId'],
			"referer"=>'https://mp.weixin.qq.com/cgi-bin/getmessage?t=wxm-message&lang=zh_CN&count=50&token='.$_D['token'],
		);
		$sendP=array(
			"ajax"=>1,
			"token"=>$_D['token']
		);
		$retObj=$this->postWxMsg($sendC,$sendP);
		print_r($retObj);
		echo "\n";
		echo "\n";
	}
	
	private function getMsg($_D){//获取消息
		$sendP=array(
			"ajax"=>1,
			"token"=>$_D['token']
		);
		$sendD=array(
		    "count"=>"100",//每页获取多少条
			"timeline"=>"0",//是否只显示今天的消息, 与day参数不能同时大于0
			"day"=>"0",//最近几天消息(1:昨天,2:前天,3:五天内)
			"star"=>"0",//是否星标组信息
			"frommsgid"=>"0",//传入最后的消息id编号,为0则从最新一条起倒序获取
			"offset"=>"10",//frommsgid起算第一条的偏移量
		);
		$sendD['frommsgid']=$sendD['frommsgid']==0?'':$sendD['frommsgid'];
		$sendC=array(
			"url"=>'https://mp.weixin.qq.com/cgi-bin/getmessage?t=ajax-message&lang=zh_CN&cgi=getmessage&count='.$sendD['count'].'&timeline='.$sendD['timeline'].'&day='.$sendD['day'].'&star='.$sendD['star'].'&frommsgid='.$sendD['frommsgid'].'&offset='.$sendD['offset'].'',
			"referer"=>'https://mp.weixin.qq.com/cgi-bin/getmessage?t=wxm-message&lang=zh_CN&count=50&token='.$_D['token'],
		);
		$retObj=$this->postWxMsg($sendC,$sendP);
		print_r($retObj);
		echo "\n";
		echo "\n";
	}
	
	public function curl($user){
		$this->_login($user);
		if(@$this->loginMsg){
			$getUrl="https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize=99999&pageidx=0&type=0&groupid=0&lang=zh_CN&token=".$this->loginMsg['token'];
			$getWX=$this->getWX($this->loginMsg['token'],$getUrl); // 获取用户列表
			preg_match_all( '/<script type="text\/javascript">([\w\W]*)<\/script>/iU', $getWX, $retGet);
			$retGet[1][2] = preg_replace('/([\w\W]*?)friendsList : \(\{"contacts":/i','',$retGet[1][2]);
			$retGet[1][2] = preg_replace('/\}\)\.contacts(.*)/si','',$retGet[1][2]);
		    $jsonRet=json_decode($retGet[1][2]);//所有用户的数组
//此处一定要这样排版，不然发出去会乱
$sendTxt='欢迎关注max哲微信公众账号
%s ↓↓↓↓↓↓↓
此次深夜群发消息做测试
日后可能会还有群发消息
谢谢关注，后期更精彩';

			//$this->getMsg($loginMsg);
			foreach($jsonRet as $k=>$v){

				$this->loginMsg['fakeId']=$v->id;
				$this->dbFriend($this->loginMsg['ID'],$v);
				$sendT=sprintf($sendTxt,$v->nick_name);
		        $this->initiativeSendMsg($this->loginMsg,$sendT);
				
				ob_flush();      
		        flush();
				sleep(1); 
		    }
	    }
	}	
}

$wx=new sM();
$a['name']="578511458@qq.com";
$a['paw']=md5("13145202008620");
$wx->curl($a);
?>

</body>
</html>