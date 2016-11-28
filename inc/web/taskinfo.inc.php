<?php 
	global $_GPC,$_W;
	
	
	if($_GPC['op'] == 'deal'){
		if(!$_W['isajax']) die;
		if($_GPC['type'] == 'accept' || $_GPC['type'] == 'refuse') $res = model_task::acceptRplyAndRefuseReply($_GPC,'web',0,$this);	
		if($_GPC['type'] == 'noscan') $res = WebCommon::isScanInTaskAndTaskinfo(1,$_GPC['replyid']); //禁止浏览
		if($_GPC['type'] == 'allowscan') $res = WebCommon::isScanInTaskAndTaskinfo(0,$_GPC['replyid']); //恢复浏览
		
		if($res) die('1');die('2');
	}
	
	if($_GPC['op'] == 'alldeal'){ //批量采纳和拒绝
		if(!$_W['isajax']) die;
		$replyinfo = model_reply::autoDealNeadTheReplyFunc(array('taskid'=>$_GPC['taskid'],'status'=>1));
		foreach($replyinfo as $k=>$v){
			$_GPC['replyid'] = $v['id'];
			$res = model_task::acceptRplyAndRefuseReply($_GPC,'web',0,$this);
		}
		if($res) die('1');die('2');	
	}
	
	//任务信息
	$taskinfo = model_task::getSingleTask($_GPC['id']);
	$taskinfo['pic'] = iunserializer($taskinfo['pic']);
	
	//回复信息
	$where['taskid'] = $_GPC['id'];
	$where['status'] = $_GPC['status'];
	if(empty($_GPC['status'])) unset($where['status']);
	
	
	$reply = model_reply::getAllTaskReply($where,$_GPC['page'],10);
	$replyinfo = $reply[0];
	foreach($replyinfo as $k=>$v){
		$replyinfo[$k]['images'] = iunserializer($v['images']);
	}
	$pager = $reply[1];
	
	
	
	include $this->template('web/taskinfo');
?>