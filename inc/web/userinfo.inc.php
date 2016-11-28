<?php 
	global $_GPC,$_W;
	$op = empty($_GPC['op'])?'basic':$_GPC['op'];
	
	if($op == 'basic'){
		$userinfo = model_user::getSingleUserInfo(array('uid'=>$_GPC['uid']));
	
	}elseif($op == 'money'){
		$money = WebCommon::getCredit2LogInUserinfoAndMoneylog($_GPC,'userinfo');
		$moneyinfo = $money[0];
		$pager = $money[1];
		
		
	}elseif($op == 'deposit'){
		
		$deposit = WebCommon::getDepositLogInUserinfoAndMoneylog($_GPC,'userinfo');	
		$depositinfo = $deposit[0];
		$pager = $deposit[1];		
		
		
	}elseif($op == 'pubtask'){
		$task = WebCommon::getTaskList($_GPC,'userinfo');
		
		$tasksort = $task[0];
		$taskinfo = $task[1][0];
		$pager = $task[1][1];
		
	//
	}elseif($op == 'replytask'){
		$reply = WebCommon::getReplyInUserinfoAndTask($_GPC,'userinfo');
		$replyinfo = $reply[0];
		$pager = $reply[1];
	
	}elseif($op == 'pubprivatetask' || $op == 'acceptprivatetask' ){		
		$task = WebCommon::getPrivateTaskList($_GPC,$op);
		$taskinfo = $task[0];
		$pager = $task[1];
		
	}elseif($op == 'paylog'){		
		$pay = WebCommon::getPayLogInUserinfoAndMoneylog($_GPC,'userinfo');
		$payinfo = $pay[0];
		$pager = $pay[1];
	}
	
	

	include $this->template('web/userinfo');
?>