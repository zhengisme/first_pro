<?php 
	global $_GPC,$_W;
	$_GPC['do'] = empty($_GPC['do'])?'task':$_GPC['do'];
	$op = empty($_GPC['op'])?'list':$_GPC['op'];
	
	
	//删除任务
	if(checksubmit('deletetask')){
		$res = WebCommon::deleteDataInWeb($_GPC['taskid'],'zb_task_tasklist');
		message('操作完成,成功删除'.$res[0].'项，失败'.$res[1].'项',$_SERVER['HTTP_REFERER'],'success');
	}
	//结算任务
	if(checksubmit('accounttask')) dealVerifyTask($_GPC,1,'accounttask',$this);
	
	//审核任务
	if(checksubmit('righttask')) dealVerifyTask($_GPC,3,'righttask',$this);
	
	//审核不通过
	if(checksubmit('errortask')) dealVerifyTask($_GPC,3,'errortask',$this);	
	
	
	//共用批量处理任务方法
	function dealVerifyTask($_GPC,$status,$from,$module){
		$s = 0;
		foreach($_GPC['taskid'] as $k=>$v){
			$taskinfo = Util::getSingleData('zb_task_tasklist',array('id'=>$v));
			if($taskinfo['status'] == $status) {
				if($from == 'righttask'){ //审核通过
					$res = model_task::verifyTaskWithPass($module,$v); 
					
				}elseif($from == 'errortask'){ //审核不通过
					$res = model_task::verifyTaskWithNoPass($module,$taskinfo); 
				
				}elseif($from == 'accounttask'){ //结算
					$res = model_task::accountTaskInAjaxdealAndCrontab($taskinfo,$module); //结算方法
				}
				if($res) $s ++;
			}
		}
		message('操作完成,成功'.$s.'项',referer(),'success');
	}
	
	
	//禁止浏览回复
	if(checksubmit('cannotscan')) dealScan(1,$_GPC);	
	
	//恢复浏览回复
	if(checksubmit('allowscan')) dealScan(0,$_GPC);	
	
	function dealScan($isscan,$_GPC){
		global $_W;
		$s = 0;$f = 0;
		foreach($_GPC['idlist'] as $k=>$v){
			$res = WebCommon::isScanInTaskAndTaskinfo($isscan,$v);
			if($res){
				$s++; 
			}else{
				$f++;
			} 
		}
		message('操作完成,成功'.$s.'项，失败'.$f.'项',$_SERVER['HTTP_REFERER'],'success');		
	}
	
	
	if($op == 'list' || $op == 'search'){
		$task = WebCommon::getTaskList($_GPC,'task');
		
		$tasksort = $task[0];
		$taskinfo = $task[1][0];
		$pager = $task[1][1];
		
	}elseif($op == 'reply'){
		$reply = WebCommon::getReplyInUserinfoAndTask($_GPC,'reply');
		$replyinfo = $reply[0];
		$pager = $reply[1];
		
	}elseif($op == 'add' || $op == 'edit'){
		
		if(checksubmit()){		
			if(empty($_GPC['id'])){
				$res = model_task::publishTask('web',$_GPC,$this);
				if($res['status'] == 1) message('不能存在空项');
				if($res['status'] == 2) message('您没有选择城市');
				if($res['status'] == 6) message('发布失败');
				if($res['status'] == 5) message('发布成功',$_SERVER['HTTP_REFERER'],'success');				
			}else{	
				$res = model_task::publishTask('web',$_GPC,$this,'edit');
				if($res) 
				message('编辑成功',$_SERVER['HTTP_REFERER'],'success');
				message('编辑失败');
			}
		}
		
		$tasksort = model_tasksort::getAllTasksort(1,100,'app');
		if($op == 'edit') {
			$taskinfo = Util::getSingleData('zb_task_tasklist',array('id'=>$_GPC['id']));
			$taskinfo['pic'] = iunserializer($taskinfo['pic']);
		}
	
	//编辑回复
	}elseif($op == 'editreply'){
		
		if(checksubmit('editreply')){
			$data['content'] = htmlspecialchars($_GPC['content']);
			$data['images'] = iserializer($_GPC['images']);
			
			$res = pdo_update('zb_task_reply',$data,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
			if($res) message('编辑成功',$_SERVER['HTTP_REFERER'],'success'); message('编辑失败');
		}
		
		$repyinfo = model_reply::getSingleReply(array('id'=>$_GPC['id']));
		$repyinfo['images'] = iunserializer($repyinfo['images']);
	
	//置顶和取消置顶
	}elseif($op == 'up'){
		upAndDownTask(1,intval($_GPC['id']));
	}elseif($op == 'down'){
		upAndDownTask(0,intval($_GPC['id']));
	}
	
	
	//置顶或取消置顶
	function upAndDownTask($type,$taskid){
		global $_W;
		$res = pdo_update('zb_task_tasklist',array('istop'=>$type),array('id'=>$taskid,'uniacid'=>$_W['uniacid']));		
		if($res) message('操作成功',referer(),'success'); message('操作失败');
	}
	

	include $this->template('web/task');
?>