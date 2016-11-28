<?php


class model_moneylog
{
	
	
	
	//查询资金记录 或者保证金记录
	static function getAllMoneyLog($tablename,$array,$page,$order='id'){
		global $_W;
		$pindex = max(1, intval($page));
		$psize = 10;
		
		$select = 'a.*,b.nickname,b.credit2,b.avatar';

		$str = Util::structWhereString($array,'a');
//		foreach((array)$array as $k=>$v ){
//			$str .= ' AND a.`'.$k.'` = '.$v.' ';
//		}
		
		$order = 'a.'.$order;
		$totalSQL = "SELECT COUNT(a.id) FROM " . tablename($tablename) . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ";
		$getSQL = "SELECT $select FROM " . tablename($tablename) . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY $order DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		$total = pdo_fetchcolumn($totalSQL);
		$moneylog = pdo_fetchall($getSQL);
		
		$pager = pagination($total, $pindex, $psize);			
		return array($moneylog,$pager);
	}
		

	
	//插入资金记录
	static function insertMoneyLog($uid,$openid,$taskid,$title,$money,$type){
		global $_W;
		if($money == 0) return false;
		$userinfo = model_user::getSingleUserInfo(array('uid'=>$uid));
		$logarray = array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$uid,
			'openid'=>$openid,
			'taskid'=>$taskid,
			'title' => $title,
			'time'=>time(),
			'money'=>$money,
			'aftermoney' => $userinfo['credit2'],
			'type'=>$type
		);
		$res = pdo_insert('zb_task_moneylog',$logarray);	
		return $res;
	}
	
}

?>