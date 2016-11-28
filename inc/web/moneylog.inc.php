<?php 
	global $_GPC,$_W;
	$op = $_GPC['op'] = empty($_GPC['op'])?'credit2log':$_GPC['op'];
	$_GPC['do'] = empty($_GPC['do'])?'moneylog':$_GPC['do'];	
	
	if($op == 'credit2log'){
		$money = WebCommon::getCredit2LogInUserinfoAndMoneylog($_GPC,'moneylog');
		$moneyinfo = $money[0];
		$pager = $money[1];
	
	
	}elseif($op == 'depositlog'){
		
		$deposit = WebCommon::getDepositLogInUserinfoAndMoneylog($_GPC,'moneylog');	
		$depositinfo = $deposit[0];
		$pager = $deposit[1];	
	
	
	}elseif($op == 'paylog'){
		$pay = WebCommon::getPayLogInUserinfoAndMoneylog($_GPC,'paylog');
		$payinfo = $pay[0];
		$pager = $pay[1];
	}
	
	
	
	include $this->template('web/moneylog');
?>