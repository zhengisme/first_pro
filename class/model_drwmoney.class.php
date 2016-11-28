<?php 

class model_drwmoney
{	
	
	
	//查询所有提现、提取保证金记录 
	static function getAllDrwmoneyLogAndDepositLog($tablename,$wherearray,$order='id',$num=10,$page=1){
		global $_W;
		$pindex = max(1, intval($page));
		$psize = $num;
		
		$str = Util::structWhereString($wherearray,'a');			
		
		if($order == 'credit2'){
			$order = " b.`".$order."` ";
		}elseif($order == 'deposit'){
			$order = " c.`".$order."` ";
		}else{
			$order = " a.`".$order."` ";
		}
		
		$select = 'a.*,b.nickname,b.credit2,b.avatar,c.deposit,c.replynumber,c.acceptednumber,c.pubnumber,c.acceptnumber';
		
		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename($tablename) . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid INNER JOIN " . tablename('zb_task_user') . " AS c ON c.uid = a.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ");
		
		$taskinfo = pdo_fetchall("SELECT $select FROM " . tablename($tablename) . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid INNER JOIN " . tablename('zb_task_user') . " AS c ON c.uid = a.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY $order DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$pager = pagination($total, $pindex, $psize);	
		
		return array($taskinfo,$pager);

	}	
	
	
	//插入提现数据
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
		$res = pdo_insert('zb_task_drwmoney',$data);
		
		if($res){
			//减钱
			$res = model_user::updateUserCredit2($userinfo['uid'],-$money);
			if($res) $res = model_moneylog::insertMoneyLog($userinfo['uid'],$userinfo['openid'],'','提现支出',-$money,3);
			model_user::deleteUserCache($userinfo['openid']);  //删除用户缓存			
			
			//发通知
			if($res) Message::dmessage($userinfo['penid'],$module,$money,$userinfo['nickname']);
			
		}
		
		if($res) return true;return false;
	}
	
	
}

?>