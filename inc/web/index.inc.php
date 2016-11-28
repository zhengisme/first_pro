<?php 
	global $_W,$_GPC;
	
	if($_GPC['op'] == 'deletecache'){
		$res = Util::deleteThisModuleCache();
		if($res) die('1'); die('2');
	}
	
	
	include $this->template('web/index');
?>