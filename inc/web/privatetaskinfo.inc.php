<?php 
	global $_GPC,$_W;
	
	//管理员判断处理投诉的任务
	if($_GPC['op'] == 'deal'){
		if(!$_W['isajax']) die;
		$type = intval($_GPC['type']);
		$reason = htmlspecialchars($_GPC['reason']);
		$taskid = intval($_GPC['taskid']);
		
		$taskinfo = model_privatetask::getSinglePrivateTaskNoInner(array('id'=>$taskid));
		if($taskinfo['status'] != 9) die;
		
		if($type == 1) $status = 13; //判给雇员
		if($type == 2) $status = 14; //判给雇主
		
		//改变任务状态
		$res = model_privatetask::updateSingleTask(array('status'=>$status,'admindealtime'=>time(),'admindealresult'=>$reason),array('id'=>$taskinfo['id']));
		
		if($type == 1) $res = model_privatetask::completeTaskInajaxdealAndCrontab($taskinfo,$status,$this,'web'); //判给雇员，发放资金等、这里已发通知
		
		if($type == 2) {
			$res = model_privatetask::backMoneyToBossInPrivateTask($taskinfo); //退资金
			Message::cmessage($taskinfo['bossopenid'],$this,$taskinfo['tasktitle'],'admindealtoboss',$taskinfo['id']);	//发通知
		}
		
		if($res) die('1');die('2');
	}
	
	
	$taskinfo = model_privatetask::getSinglePrivateTaskNoInner(array('id'=>$_GPC['id']));
	$taskinfo['images'] = iunserializer($taskinfo['images']);
	$taskinfo['completecontent'] = iunserializer($taskinfo['completecontent']);
	
	if(!empty($taskinfo)){
		$bossinfo = model_user::getSingleUserInfo(array('uid'=>$taskinfo['bossuid']));
		$workerinfo = model_user::getSingleUserInfo(array('uid'=>$taskinfo['workeruid']));		
	}
	
	
	
	include $this->template('web/privatetaskinfo');
?>