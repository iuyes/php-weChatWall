<?php
/**
* @author    MAXming
* @call      18825142536
* @version   2013-10-25
*/

class CC{

	public function gCookies($_url, $_D, $_P){//获取cookies
		$chs = curl_init();
		curl_setopt($chs, CURLOPT_URL, $_url);
		curl_setopt($chs, CURLOPT_SSL_VERIFYPEER,FALSE);
	    curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($chs, CURLOPT_HEADER, 0);                 //不输出头部
		curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);         //文件形式输出html
		curl_setopt($chs, CURLOPT_POST, 1);
		curl_setopt($chs, CURLOPT_POSTFIELDS, $_P);
		curl_setopt($chs, CURLOPT_COOKIEJAR, $_D['cookies']);
		curl_setopt($chs, CURLOPT_REFERER, $_D['referer']);
		curl_setopt($chs, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0");
		$html=curl_exec($chs);
		curl_close($chs);
		if(@!empty($html)){
			return $html;
		}else{
			return false;
		}
	}

	public function gHtml($_url, $_D){//获取页面
		$chs = curl_init();
		curl_setopt($chs, CURLOPT_URL, $_url);
		curl_setopt($chs, CURLOPT_SSL_VERIFYPEER,FALSE);
	    curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($chs, CURLOPT_HEADER, 0); //不输出头部
		curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1); //文件形式输出html
		curl_setopt($chs, CURLOPT_COOKIEFILE, $_D['cookies']);
		curl_setopt($chs, CURLOPT_REFERER, $_D['referer']);
		curl_setopt($chs, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0");
		$html=curl_exec($chs);
		curl_close($chs);
		if(@!empty($html)){
			return $html;
		}else{
			return false;
		}
	}

	public function postMsg($_url, $_D, $_P){//post 数据
		$chs = curl_init();
		curl_setopt($chs, CURLOPT_URL, $_url);  
		curl_setopt($chs, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($chs, CURLOPT_HEADER, 0);
		curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chs, CURLOPT_POST, 1);
		curl_setopt($chs, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0");
		curl_setopt($chs, CURLOPT_REFERER, $_D['referer']);
		curl_setopt($chs, CURLOPT_POSTFIELDS, $_P);
		curl_setopt($chs, CURLOPT_COOKIEFILE, $_D['cookies']);
		$html=curl_exec($chs);
		curl_close($chs);
		if(@!empty($html)){
			return $html;
		}else{
			return false;
		}
	}

}
?>