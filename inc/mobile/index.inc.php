<?php
	global $_W,$_GPC;
	$userinfo = model_user::initUserInfo(); //用户信息
	$_GPC['do'] = empty($_GPC['do'])?'index':$_GPC['do'];
	
	$_GPC['op'] = empty($_GPC['op'])?'new':$_GPC['op'];

	$initParams = array(
		'title' => '社会化众测',
		'insertelem' =>'.zc-index-card'
	);
	$wx_share  = [
		'stitle'    => '社会化众测',
		'sdesc'     => $_W['account']["name"],
		'slink'     => $_W['siteroot'] . 'app/' . $this->createMobileUrl('index'),
		'simgUrl'   => $_W['attachurl']."/headimg_".$_W['uniacid'].".jpg",
		'hideMenu'  => intval(0),
	];
	include $this->template('index');
?>