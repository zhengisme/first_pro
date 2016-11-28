<?php
	global $_W,$_GPC;
	$system    = $this->module['config'];
	$userinfo = model_user::initUserInfo(); //用户信息
	$replyname = $this->request->reply_name;
	$datafrom  = $this->request->datafrom;
	$sectionid = $this->request->sectionid;
	$wx_share  = [
		'stitle'   => '',
		'sdesc'    => '',
		'slink'    => '',
		'simgUrl'  => '',
		'hideMenu' => intval(1),
	];


	$wx_share = [
		'stitle'   =>'评论',//去掉表情
		'sdesc'    => '评论',
		'slink'    => '',
		'simgUrl'  => '',
		'hideMenu' => intval(1),
	];


	$initParams = array(
		'title' => $ptitle,
		'servermoney' => $this->module['config']['servermoney'],
		'leastserver' => $this->module['config']['leastserver'],
		'leasttaskmoney' => $this->module['config']['leasttaskmoney'],
		'isverify' => $this->module['config']['isverify'],		
		'insertelem' => ''
	);	

	include $this->template('reply');
?>