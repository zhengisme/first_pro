<?php 
	global $_W,$_GPC;
	$userinfo = model_user::initUserInfo(); //用户信息
	$_GPC['taskid'] = $_GPC['id'] =  $_GPC['taskid'] ? $_GPC['taskid'] :$_GPC['id'];
	$taskid = $_GPC['taskid'];
	$taskinfo = model_task::getSingleTask($taskid); //任务信息
	$tobe = $_GPC['tobe'];
	if(empty($taskinfo)){
		message("任务不存在");
	}

	/*if(time()>=$taskinfo["overtime"] && $taskinfo["status"]==1 ){
		$taskinfo["status"] = 2;
		//结束任务
		$res = model_task::accountTaskInAjaxdealAndCrontab($taskinfo,$this);
	}*/

	if (strpos($v['pic'], 'http') === false) {
		$taskinfo['simg'] = $_W['attachurl'] . $taskinfo['pic'];
	}else{
		$taskinfo['simg'] = $taskinfo['pic'];
	}

	$creator = medoo()->get("zb_task_user",'*',['uid'=>$taskinfo["uid"]]);

	if($creator["state"] ==2 && !empty($creator['guydesc'])){
		$creator['guydesc'] = json_decode($creator['guydesc'],true);
		$taskinfo["avatar"] = $creator['guydesc']['pic'];
		$taskinfo["nickname"] = $creator['guydesc']['manuname'];
		$creator["avatar"] = $creator['guydesc']['pic'];
		$creator["nickname"] = $creator['guydesc']['manuname'];
	}
//	$taskinfo["money"] = $taskinfo["money"]*$taskinfo["number"];
	//不是自己发布的直接跳转到发布任务页面
	/*if($taskinfo['uid'] != $userinfo['uid'] && ($taskinfo['status'] == 3 || $taskinfo['status'] == 4) && $_W['openid'] != $this->module['config']['adminopenid']){
		header('Location: '.$this->createMobileUrl('pub',array('op'=>'pub')));
		die;
	} */
	//是否关注
	if($taskinfo["uid"] != $userinfo['uid']){
		$filter   = [
			'AND' => [
				'uniacid' => $this->conf->uniacid, 'fid' => $taskinfo["uid"], 'uid' => $userinfo['uid'],
			],
		];
		$followed = medoo()->has("zb_task_follow", $filter);
	}
	$task_time = date('Y-m-d', $taskinfo["createtime"]);
	$task_extra = json_decode( $taskinfo['extra'],true);
	//任务总回答数
	$taskanswercount = medoo()->count('zb_task_reply',['taskid'=>$taskid]);

	//是否收藏任务 、是否有回答过
	$loveinfo = model_love::getSingleLove($_GPC['taskid'],$userinfo['uid']);

	//获取是否有回答过
	$agoreply = medoo()->get('zb_task_reply','*',[
		'AND'=>[
			'taskid'=>$taskid,
			'uid'=>$userinfo['uid'],
		],
	]);
	empty($agoreply) ? $alreadyanswer = 0 :$alreadyanswer = 1;
	$sharedesc = $taskinfo['title'];
	//分享
	$wx_share  = [
		'stitle'    => $taskinfo['title'],
		'sdesc'     => $sharedesc,
		'slink'     => $_W['siteroot'] . 'app/' . $this->createMobileUrl('task', ['taskid' => $taskinfo["id"],'op'=>'task']),
		'simgUrl'   => $_W['attachurl'] . $taskinfo['pic'],
		'hideMenu'  => intval(0),
	];
	$longurl = 'http://api.t.sina.com.cn/short_url/shorten.json?source=1681459862&url_long='.urlencode($wx_share['slink']);
	try{
		$response = file_get_contents($longurl);
	}catch (Exception $e)
	{
		//display custom message
	}
	if(is_error($response)) {
		$result = error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
		$short_url = $wx_share['slink'];
	}else{
		$shortRes = @json_decode($response);
		$shortRes = $shortRes[0];
		if(empty($shortRes)){
			$short_url = $wx_share['slink'];
		}else{
			$short_url = $shortRes->url_short;
		}
	}
	if(empty($short_url)){
		$short_url = $wx_share['slink'];
	}
	$initParams = array(
		'title' => strip_tags(htmlspecialchars_decode(htmlspecialchars_decode($taskinfo['title']))),
		'insertelem' => '.m-answer-list',
		'servermoney' => $this->module['config']['servermoney'],
		'leastserver' => $this->module['config']['leastserver'],
		'urgleastmoney' => $this->module['config']['urgleastmoney']
	);
include $this->template('task');
?>