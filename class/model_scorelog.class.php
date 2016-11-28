<?php 

class model_scorelog
{
	
	
	//插入积分记录
	static function insertScoreLog($uid,$openid,$score,$type){
		global $_W;
		if($score == 0) return false;
		$logarray = array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$uid,
			'openid'=>$openid,
			'time'=>time(),
			'money'=>$score,
			'type'=>$type
		);
		$res = pdo_insert('zb_task_scorelog',$logarray);		
		return $res;
	}
	
}

?>