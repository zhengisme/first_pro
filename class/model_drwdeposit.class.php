<?php 

class model_drwdeposit
{
	
	//插入提取数据
	static function insertDrwLog($userinfo,$money,$module){
		global $_W;
		$data =array(
			'uniacid' => $_W['uniacid'],
			'createtime' => time(),
			'uid' => $userinfo['uid'],
			'openid'=> $userinfo['openid'],
			'money' => $money,
			'status' => 0
		);
		$res = pdo_insert('zb_task_drwdeposit',$data);
		
		if($res){
			
			//变化保证金
			$res = Util::addAndMinusData('zb_task_user',array('deposit'=>-$money),array('uid'=>$userinfo['uid']));
			if($res) $res = model_depositlog::insertDepositlogData($userinfo,-$money,2,'提取保证金'); //这里使用负数金额
			model_user::deleteUserCache($userinfo['openid']);  //删除用户缓存
			
			//发通知
			//if($res) Message::dmessage($userinfo['penid'],$module,$money,$userinfo['nickname']);
		}
		
		if($res) return true;return false;
	}
}

?>