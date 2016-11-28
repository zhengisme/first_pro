<?php 

class model_depositlog
{
	
	static function insertDepositlogData($userinfo,$money,$type,$title){
		global $_W;
		$userinfo = model_user::getSingleUserInfo(array('uid'=>$userinfo['uid']));
		$data = array(
			'uniacid' => $_W['uniacid'],
			'uid' => $userinfo['uid'],
			'openid' => $userinfo['openid'],
			'money' => $money,
			'aftermoney' => $userinfo['deposit'],
			'type' => $type,
			'title' => $title,
			'time' => time()
		);
		$res = pdo_insert('zb_task_depositlog',$data);
		model_user::deleteUserCache($userinfo['openid']);  //删除用户缓存
		if($res) return true;return false;
		
	}
	
	
	
}

?>