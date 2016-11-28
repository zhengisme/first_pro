<?php 
	global $_GPC,$_W;
	$_GPC['do'] = empty($_GPC['do'])?'privatetask':$_GPC['do'];
	$op = empty($_GPC['op'])?'list':$_GPC['op'];
	
	
	
	
	if($op == 'list'){
		$task = $task = WebCommon::getPrivateTaskList($_GPC,$op);
		$taskinfo = $task[0];
		$pager = $task[1];
		
	}elseif($op == 'delete'){
		if(checksubmit()){
			$res = WebCommon::deleteDataInWeb($_GPC['taskid'],'zb_task_privatetask');
			message('操作完成,成功删除'.$res[0].'项，失败'.$res[1].'项',$_SERVER['HTTP_REFERER'],'success');
		}
	}
	
	

	include $this->template('web/privatetask');
?>