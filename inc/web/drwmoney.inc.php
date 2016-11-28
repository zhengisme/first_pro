<?php 
	global $_GPC,$_W;
	$_GPC['do'] = empty($_GPC['do'])?'drwmoney':$_GPC['do'];
	$op = $_GPC['op'] = empty($_GPC['op'])?'waitpay':$_GPC['op'];
	
	
	//支付
	if(checksubmit('payall')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwmoney',$_GPC,'payall','drwmoney',$this);
	
	//退回提现
	if(checksubmit('toback')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwmoney',$_GPC,'toback','drwmoney',$this);
	
	//拒绝支付
	if(checksubmit('refusepay')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwmoney',$_GPC,'refusepay','drwmoney',$this);
	
	//恢复到提现列表
	if(checksubmit('recover')) WebCommon::dealMoneyAndDepositInDrwmoneyAndDeposit('zb_task_drwmoney',$_GPC,'recover','drwmoney',$this);
	
	
	if(in_array($op,array('waitpay','payed','back','refuse'))){
		$money = WebCommon::getDrwmoneyLogAndDepositLog($_GPC,'drwmoney'); 
		$moneyinfo = $money[0];
		$pager = $money[1];
	}
	
	
	include $this->template('web/drwmoney');
?>