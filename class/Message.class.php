<?php

class Message 
{
	
	/*
	答案采纳提醒
	{{first.DATA}}
	问题标题：{{keyword1.DATA}}
	所得金额：{{keyword2.DATA}}
	时间：{{keyword3.DATA}}
	{{remark.DATA}}
	编号：OPENTM207514827[标题：答案采纳提醒]
	*/
	public static function fmessage($openid,$module,$fee,$title,$taskid) {
		global $_W;
		$title = strip_tags(htmlspecialchars_decode(htmlspecialchars_decode($title)));
		$url2 = Util::taskModuleCreateUrl('task',array('id'=>$taskid,"taskid"=>$taskid,'op'=>'task'));
		$i_item = '您的回复的任务被作者采纳了，奖励已发放到您的余额中。您可以点击此处查看此任务。';
		$i_id = $module -> module['config']['f_id'];
		$i_remark = $module -> module['config']['f_remark'];
		$msg_json = '{
           	"touser":"' . $openid . '",
           	"template_id":"' . $i_id . '",
           	"url":"' . $url2 . '",
           	"topcolor":"#173177",
           	"data":{
               	"first":{
                   "value":"' . $i_item .'",
                   "color":"#3279c6"
               	},
               	"keyword1":{
					"value":"' . $title .'",
               		"color":"#3279c6"
				},							
               	"keyword2":{
					"value":"' . $fee .'",
               		"color":"#ff5f27"
				},
               	"keyword3":{
					"value":"' . date('Y-m-d H:i:s',time()) .'",
               		"color":"#000000"
				},				
               	"remark":{
                   "value":"' . $i_remark . '",
                   "color":"#3279c6"
               	}
           	}
        }';
		return self::commonPostMessage($msg_json);
	}

	/*
		提现、提取保证金到账通知
		{{first.DATA}}
		到账金额：{{keyword1.DATA}}
		到账时间：{{keyword2.DATA}}
		到账详情：{{keyword3.DATA}}
		{{remark.DATA}}
		编号：OPENTM400265867[标题：到账提醒]
	*/
	public static function emessage($openid,$module,$fee,$from) {
		global $_W;
		$url2 = Util::taskModuleCreateUrl('index',array('op'=>'new'));
		if($from == 'drwmoney') $itemstr = '提现';
		if($from == 'deposit') $itemstr = '提取保证金';
		$i_item = '您的'.$itemstr.'申请已支付，资金已发放到您的微信钱包中，请注意查收。您可以点击此处查看最新的高价任务。';
		$i_id = $module -> module['config']['e_id'];
		$i_remark = $module -> module['config']['e_remark'];
		$msg_json = '{
           	"touser":"' . $openid . '",
           	"template_id":"' . $i_id . '",
           	"url":"' . $url2 . '",
           	"topcolor":"#173177",
           	"data":{
               	"first":{
                   "value":"' . $i_item .'",
                   "color":"#3279c6"
               	},
               	"keyword1":{
					"value":"' . $fee .'",
               		"color":"#3279c6"
				},							
               	"keyword2":{
					"value":"' . date('Y-m-d H:i:s',time()) .'",
               		"color":"#000000"
				},
               	"keyword3":{
					"value":"发放到微信钱包",
               		"color":"#173177"
				},					
               	"remark":{
                   "value":"' . $i_remark . '",
                   "color":"#3279c6"
               	}
           	}
        }';
		return self::commonPostMessage($msg_json);
	}



	/*
		提现申请通知
		{{first.DATA}}
		本次提现金额：{{keyword1.DATA}}
		提现账户：{{keyword2.DATA}}
		提现申请时间：{{keyword3.DATA}}
		预计到账时间：{{keyword4.DATA}}
		{{remark.DATA}}
		编号：OPENTM207292959[名称：提现申请提醒]
	*/
	public static function dmessage($openid,$module,$fee,$nickname) {
		global $_W;
		$url2 = Util::taskModuleCreateUrl('user',array('op'=>'money'));
		$i_item = '您的提现已申请成功，请等待财务审核，资金会直接发送到您的微信钱包中。您可以点击此处查看记录。';
		$i_id = $module -> module['config']['d_id'];
		$i_remark = $module -> module['config']['d_remark'];
		$msg_json = '{
           	"touser":"' . $openid . '",
           	"template_id":"' . $i_id . '",
           	"url":"' . $url2 . '",
           	"topcolor":"#173177",
           	"data":{
               	"first":{
                   "value":"' . $i_item .'",
                   "color":"#3279c6"
               	},
               	"keyword1":{
					"value":"' . $fee .'",
               		"color":"#3279c6"
				},							
               	"keyword2":{
					"value":"' . $nickname .'",
               		"color":"#ff5f27"
				},
               	"keyword3":{
					"value":"' . date('Y-m-d H:i:s',time()) .'",
               		"color":"#000000"
				},	
               	"keyword3":{
					"value":"24小时内",
               		"color":"#173177"
				},					
               	"remark":{
                   "value":"' . $i_remark . '",
                   "color":"#3279c6"
               	}
           	}
        }';
		return self::commonPostMessage($msg_json);
	}




	/*
		私包任务变化通知
		{{first.DATA}}
		任务标题：{{keyword1.DATA}}
		时间：{{keyword2.DATA}}
		状态：{{keyword3.DATA}}
		{{remark.DATA}}
		编号：OPENTM207874703[标题：任务通知]
	*/
	public static function cmessage($openid,$module,$title,$from,$taskid) {
		global $_W;
		$url2 = Util::taskModuleCreateUrl('privatetask',array('id'=>$taskid));
		if($from == 'paytaskmoney'){
			$statusstr = '等待您完成任务';
			$i_item = '您索要的任务被雇主接受并支付了赏金，点击此处可去任务页面查看详情。';
		}elseif($from == 'refusegeivetask'){
			$statusstr = '任务已取消';
			$i_item = '您索要的任务被雇主拒绝了，点击此处可去任务页面查看详情。';
		}elseif($from == 'completetask'){
			$statusstr = '等待您审核任务';
			$i_item = '您有任务被雇员完成了，点击此处可去任务页面查看详情。';
		}elseif($from == 'canceltask'){
			$statusstr = '任务已取消';
			$i_item = '雇员主动取消了正在执行的任务，资金已退回到您的余额中。点击此处可去任务页面查看详情。';
		}elseif($from == 'confirmtask'){
			$statusstr = '任务已完成';
			$i_item = '雇主确认完成了任务，奖励已发放到您的余额中，点击此处可去任务页面查看详情。';
		}elseif($from == 'confirmrefuse'){
			$statusstr = '等待您确认';
			$i_item = '雇主拒绝了您执行的任务结果，请及时与雇主沟通，以免产生矛盾。点击此处可去任务页面查看详情。';
		}elseif($from == 'acceptrefuse'){
			$statusstr = '任务已取消';
			$i_item = '雇员接受了您对任务结果的拒绝。点击此处可去任务页面查看详情。';
		}elseif($from == 'omplainboss'){
			$statusstr = '客服协调中';
			$i_item = '您对任务的结果的拒绝被雇员投诉，请等待客服的处理。点击此处可去任务页面查看详情。';
		}elseif($from == 'workertaketask'){
			$statusstr = '任务执行中';
			$i_item = '您发送的任务被雇员接受了，点击此处可去任务页面查看详情。';
		}elseif($from == 'workerrefusetask'){
			$statusstr = '任务已取消';
			$i_item = '您发送的任务被雇员拒绝了，点击此处可去任务页面查看详情。';
		}elseif($from == 'admindealtoboss'){
			$statusstr = '任务已取消';
			$i_item = '您有一个申诉中的任务已经管理员处理后将资金退还到您的余额中。点击此处可去任务页面查看详情。';
		}elseif($from == 'admindealtoworker'){
			$statusstr = '任务已完成';
			$i_item = '您有一个申诉中的任务已经管理员处理后将资金发放到您的余额中。点击此处可去任务页面查看详情。';
		}elseif($from == 'pubtask'){
			$statusstr = '已发布';
			$i_item = '有会员发布了一个普通任务，点击此处可去任务页面查看详情。';
			$url2 = Util::taskModuleCreateUrl('task',array('id'=>$taskid));
		}

		$title = strip_tags(htmlspecialchars_decode(htmlspecialchars_decode($title)));

		$i_id = $module -> module['config']['c_id'];
		$i_remark = $module -> module['config']['c_remark'];
		$msg_json = '{
           	"touser":"' . $openid . '",
           	"template_id":"' . $i_id . '",
           	"url":"' . $url2 . '",
           	"topcolor":"#173177",
           	"data":{
               	"first":{
                   "value":"' . $i_item .'",
                   "color":"#3279c6"
               	},
               	"keyword1":{
					"value":"' . $title .'",
               		"color":"#3279c6"
				},							
               	"keyword2":{
					"value":"' . date('Y-m-d H:i:s',time()) .'",
               		"color":"#000000"
				},
               	"keyword3":{
					"value":"'. $statusstr .'",
               		"color":"#173177"
				},				
               	"remark":{
                   "value":"' . $i_remark . '",
                   "color":"#3279c6"
               	}
           	}
        }';
		return self::commonPostMessage($msg_json);
	}



	/*
		收到回复通知
		{{first.DATA}}
		回复者：{{keyword1.DATA}}
		回复时间：{{keyword2.DATA}}
		回复内容：{{keyword3.DATA}}
		{{remark.DATA}}
		编号：OPENTM203574543[标题：收到回复通知]
	*/
	public static function bmessage($openid,$module,$nickname,$content,$taskid) {
		global $_W;
		$url2 = Util::taskModuleCreateUrl('task',array('id'=>$taskid,'taskid'=>$taskid,'op'=>'task'));
		$i_item = '有人回复了您的任务，请尽快处理此回复。您可以点击此处去处理任务。';
		$i_id = $module -> module['config']['b_id'];
		$i_remark = $module -> module['config']['b_remark'];
		$msg_json = '{
           	"touser":"' . $openid . '",
           	"template_id":"' . $i_id . '",
           	"url":"' . $url2 . '",
           	"topcolor":"#173177",
           	"data":{
               	"first":{
                   "value":"' . $i_item .'",
                   "color":"#3279c6"
               	},
               	"keyword1":{
					"value":"' . $nickname .'",
               		"color":"#3279c6"
				},							
               	"keyword2":{
					"value":"' . date('Y-m-d H:i:s',time()) .'",
               		"color":"#000000"
				},
               	"keyword3":{
					"value":"'. $content .'",
               		"color":"#173177"
				},				
               	"remark":{
                   "value":"' . $i_remark . '",
                   "color":"#3279c6"
               	}
           	}
        }';
		return self::commonPostMessage($msg_json);
	}

	/*
		收到私包任务提醒 type1是索要的 type2是发送的
		{{first.DATA}}
		任务名称：{{keyword1.DATA}}
		悬赏金额：{{keyword2.DATA}}
		完成时间：{{keyword3.DATA}}
		{{remark.DATA}}
		编号：OPENTM401972662[标题：新任务提醒]
	*/
	public static function amessage($openid,$module,$title,$fee,$limittime,$taskid,$type=1) {
		global $_W;
		$title = strip_tags(htmlspecialchars_decode(htmlspecialchars_decode($title)));
		$url2 = Util::taskModuleCreateUrl('privatetask',array('id'=>$taskid));
		$i_item = ($type == 1)?'有人向你索要一个私包任务，您可以点击此处去处理任务。':'有人给你发了一个私包任务，您可以点击此处去处理任务';
		$i_id = $module -> module['config']['a_id'];
		$i_remark = $module -> module['config']['a_remark'];
		$msg_json = '{
           	"touser":"' . $openid . '",
           	"template_id":"' . $i_id . '",
           	"url":"' . $url2 . '",
           	"topcolor":"#173177",
           	"data":{
               	"first":{
                   "value":"' . $i_item .'",
                   "color":"#3279c6"
               	},
               	"keyword1":{
					"value":"' . $title .'",
               		"color":"#3279c6"
				},							
               	"keyword2":{
					"value":"' . $fee .'元",
               		"color":"#ff5f27"
				},
               	"keyword3":{
					"value":"任务限时'. $limittime .'小时",
               		"color":"#173177"
				},				
               	"remark":{
                   "value":"' . $i_remark . '",
                   "color":"#3279c6"
               	}
           	}
        }';
		return self::commonPostMessage($msg_json);
	}	
	
	//模板消息url
	static function getUrl1(){
		load() -> model('account');
		$access_token = WeAccount::token();
		$url1 = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token . "";		
		return $url1;
	}
	
	static function commonPostMessage($msg_json){
		$url1 = self::getUrl1();
		$res = Util::httpPost($url1, $msg_json);
		$res = json_decode($res,true);
		if($res['errmsg'] == 'ok') return true;return false;
	}	
	
	
}
?>