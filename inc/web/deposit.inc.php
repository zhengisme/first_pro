<?php 
	global $_GPC,$_W;
	$op = $_GPC['op'] = empty($_GPC['op'])?'waitpay':$_GPC['op'];
		
	
	//支付
	if(checksubmit('payall')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwdeposit',$_GPC,'payall','deposit',$this);
	
	//退回提现
	if(checksubmit('toback')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwdeposit',$_GPC,'toback','deposit',$this);
	
	//拒绝支付
	if(checksubmit('refusepay')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwdeposit',$_GPC,'refusepay','deposit',$this);
	
	//恢复到提现列表
	if(checksubmit('recover')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwdeposit',$_GPC,'recover','deposit',$this);	
	
	
	if(in_array($op,array('waitpay','payed','back','refuse'))){
		$money = WebCommon::getDrwmoneyLogAndDepositLog($_GPC,'drwdeposit');
		$depositinfo = $money[0];
		$pager = $money[1];
	
	}elseif($op == 'add' || $op == 'admin'){
		$deposit = WebCommon::getDepositLogInUserinfoAndMoneylog($_GPC,'deposit');	
		$depositinfo = $deposit[0];
		$pager = $deposit[1];
	}
	
	
	include $this->template('web/deposit');
?>