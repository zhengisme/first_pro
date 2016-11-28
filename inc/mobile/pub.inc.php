<?php
	global $_W,$_GPC;
	$userinfo = model_user::initUserInfo(); //用户信息	
	$_GPC['op'] = empty($_GPC['op'])?'pub':$_GPC['op'];
	$puburl = $this->createMobileUrl('pub',array('op'=>'pub'));
	$pubedurl = $this->createMobileUrl('user',array('op'=>'userpubed'));


	

	//TODO 前期固定数据，后期后台配置
	$staticpubParam = Util::$staticpubParam;
	$taskcoverarr = Util::$taskcoverarr;

	$sysdata = date("Y-m-d");

	$initParams = array(
		'title' => '发布任务',
		'servermoney' => $this->module['config']['servermoney'],
		'leastserver' => $this->module['config']['leastserver'],
		'leasttaskmoney' => $this->module['config']['leasttaskmoney'],
		'isverify' => $this->module['config']['isverify'],
		'insertelem' => '.pub_list'
	);

	include $this->template('pub');
?>