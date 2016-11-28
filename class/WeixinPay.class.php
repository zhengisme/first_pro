<?php 

class WeixinPay
{
	
	
	public function sendMoneyToUser($arr) {
		global $_W;
		$setting = uni_setting($_W['uniacid'], array('payment'));
		
		$data['mch_appid'] = $_W['account']['key'];
		$data['mchid'] = $setting['payment']['wechat']['mchid'];
		//设备号
		$data['nonce_str'] = $this->createNoncestr();
		$data['partner_trade_no'] = $data['mchid'].date("Ymd",time()).date("His",time()).rand(1111,9999);		
		$data['openid'] = $arr['openid'];		
		$data['check_name'] = 'NO_CHECK';
		$data['amount'] = $arr['fee']*100;		
		$data['desc'] = '用户提现';
		$data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
		$data['sign'] = $this->getSign($data);
		if(!$data['openid']) 
		{
			$rearr['return_msg']='缺少用户openid';
			return $rearr;
		}
		$xml = $this->arrayToXml($data);
		$url ="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
		$re = $this->wxHttpsRequestPem($xml,$url);
		$rearr = $this->xmlToArray($re);
		return $rearr;
	}
	
	public function createNoncestr( $length = 32 ) {
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ ) 
		{
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}
	
	function formatBizQueryParaMap($paraMap, $urlencode) {
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v) 
		{
			if($urlencode) 
			{
				$v = urlencode($v);
			}
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0) 
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	
	public function getSign($Obj) {
		$setting = uni_setting($_W['uniacid'], array('payment'));
		foreach ($Obj as $k => $v) 
		{
			$Parameters[$k] = $v;
		}
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		$String = $String."&key=".$setting['payment']['wechat']['apikey'];
		$String = md5($String);
		$result_ = strtoupper($String);
		return $result_;
	}
	
	public function arrayToXml($arr) {
		$xml = "<xml>";
		foreach ($arr as $key=>$val) 
		{
			if (is_numeric($val)) 
			{
				$xml.="<".$key.">".$val."</".$key.">";
			}
			else $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
		}
		$xml.="</xml>";
		return $xml;
	}
	public function xmlToArray($xml) {
		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $array_data;
	}
	
	public function wxHttpsRequestPem( $vars,$url, $second=30,$aHeader=array()) {
		global $_W;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT,zb_task.'/cert/'.$_W['uniacid'].'/apiclient_cert.pem');
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY,zb_task.'/cert/'.$_W['uniacid'].'/apiclient_key.pem');
		curl_setopt($ch,CURLOPT_CAINFO,'PEM');
		curl_setopt($ch,CURLOPT_CAINFO,zb_task.'/cert/'.$_W['uniacid'].'/rootca.pem');
		if( count($aHeader) >= 1 )
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		$data = curl_exec($ch);
		if($data)
		{
			curl_close($ch);
			return $data;
		}
		else 
		{
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n";
			curl_close($ch);
			return false;
		}
	}
	
	
}

?>