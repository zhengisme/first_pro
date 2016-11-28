<?php
	global $_W,$_GPC;
	$userinfo = model_user::initUserInfo(); //用户信息	
	$sectionid = $_GPC['id'];
	$tobe = $_GPC['tobe'];
	$section = medoo()->get('zb_task_section','*',['id'=>$sectionid,]);
	if (empty($section)) {
		message('该贴子已删除');
	}

	/**
	 * 获取贴子所属回答
	 */
	$answer= medoo()->get('zb_task_amswer', '*', [
		"AND"=>[
			'uniacid'=>$_W['uniacid'],
			'taskid'=>$section["taskid"],
			'uid'=>$section['uid'],
		],
	]);
	$difftime = $section['time'] - $answer['time'];
	$inday = intval($difftime/60/60/24);
	$inday<1 ? $inday = 1 :$inday += 1 ;
	$section["inday"] = $inday; //第几天
	$section["showtime"] = date('Y-m-d', $section["time"]);//日期
	$section["images"] = json_decode($section["images"],true); //图片array
	$section["imgsum"] = count($section["images"]);//图片数

	/**
	 * 创建者
	 */
	$creatorwhere['uid'] = $section['uid'];
	$creator = model_user::getSingleUserInfo($creatorwhere);

	$sharetitle  = $sharedesc = mb_substr($section['content'], 0, 34, 'utf-8');
	$shareimg = '';
	if (!empty($simgs)) {
		$shareimg = $_W['attachurl'] . $section["images"][0]["w"];
	}
/*	$likesql             = "SELECT headimgurl,fansid FROM " . tablename("zb_task_like") . "
										WHERE uniacid = '{$_W['uniacid']}' and sectionid = '{$section['id']}' ORDER BY id  limit 10";
	$likelist            = pdo_fetchall($likesql);
	$section['likelist'] = $likelist;

	$section['likesum'] = count($likelist);//点赞数
	if ($section['likesum'] == 10) {
		$section['likesum'] = medoo()->count($this->liketable, [
			'AND' => ['uniacid' => $this->conf->uniacid, 'sectionid' => $section['id'],],
		]);
	}

	$section['dolike'] = medoo()->has($this->liketable, [
		'AND' => ['fansid' => $fans['id'], 'uniacid' => $this->conf->uniacid, 'sectionid' => $section['id'],],
	]);*/

	$section['likesum'] = medoo()->count("zb_task_like",['AND'=>[
		'sectionid'=>$sectionid,
		'type'=>2,
		'pro'=>1,
		]
	]);
	$section["dolike"] = medoo()->count('zb_task_like',[
		'AND'=>[
			'sectionid'=>$sectionid,
			'uid'=>$userinfo["uid"],
			'type'=>2,
			'pro'=>1,
		]
	]);

	//打赏次数和打赏人列表
//	$section['rewardlist'] = $rewardlist;
	$replylist  =  medoo()->select("zb_task_comment",'*',[
		"AND"=>[
			'sectionid'=>$sectionid,
		],
		'ORDER'=>'id desc'
	]);
	$section['replylist']  = $replylist;
	$section['replysum']   = count($replylist);
	$systime               = TIMESTAMP;

	$wx_share = [
		'stitle'   =>$sharetitle,//去掉表情
		'sdesc'    => $sharetitle ,
		'slink'    => $_W['siteroot'] . 'app/' . $this->createMobileUrl('sdetail', ['id' => $sectionid,]),
		'simgUrl'  => $shareimg,
		'hideMenu' => intval(0),
	];
	//统计
//	$this->topicStat($section['topicid'], $sectionid);

//	$stateArray = $this->stat($section['topicid'], $sectionid);


	$initParams = array(
		'title' => '回答详情',
		'servermoney' => $this->module['config']['servermoney'],
		'leastserver' => $this->module['config']['leastserver'],
		'leasttaskmoney' => $this->module['config']['leasttaskmoney'],
		'isverify' => $this->module['config']['isverify'],		
		'insertelem' => ''
	);

	$a = Util::taskStatAdd([
		'objectid'  => $section['replyid'],
		'sectionid' => $sectionid,
		'type'      => 2,
		'like'      => 0,
	]);

	include $this->template('sdetail');
?>