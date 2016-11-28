<?php
	global $_W,$_GPC;
	$userinfo = model_user::initUserInfo(); //用户信息	
	$_GPC['op'] = empty($_GPC['op'])?'pub':$_GPC['op'];
	$taticanswerParam = Util::$staticanswerParam;
	$taskid = $_GPC['taskid'];
	$befrom = $_GPC["befrom"];
 	$alreadyanswer = 0;
	//获取是否有回答过
	$reply = medoo()->get('zb_task_reply','*',[
		'AND'=>[
			'taskid'=>$taskid,
			'uniacid'=> $_W["uniacid"],
			'uid'=>$userinfo['uid'],
		],
	]);

	$asindex  = 1;
	if(!empty($reply)){
		$sectionExtra = $reply['extra'];
		$asindex = $reply["replynum"]+1;
		if($reply["state"]==1){
			message("已结束试验");
		}
	}
	if($asindex >1){
		$alreadyanswer = 1;
	}
	$initParams = array(
		'title' => '执行任务',
		'servermoney' => $this->module['config']['servermoney'],
		'leastserver' => $this->module['config']['leastserver'],
		'leasttaskmoney' => $this->module['config']['leasttaskmoney'],
		'isverify' => $this->module['config']['isverify'],		
		'insertelem' => ''
	);	

	include $this->template('answer');
?>