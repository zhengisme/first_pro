<?php 
	
class model_love
{
	
	//批量查询
	static function getMyTaskLove($array,$page){
		global $_W;			
		$pindex = max(1, intval($page));
		$psize = 10;	
		
		$select = 'b.*,c.nickname,c.credit2,c.avatar';

		$str = '';
		foreach((array)$array as $k=>$v ){
			$str .= ' AND a.`'.$k.'` = '.$v.' ';
		}
	
		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename('zb_task_love') . " AS a INNER JOIN " . tablename('zb_task_tasklist') . "  AS b ON a.taskid = b.id INNER JOIN " . tablename('mc_members') . " AS c ON c.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' AND b.`uniacid` ='{$_W['uniacid']}' AND c.`uniacid` ='{$_W['uniacid']}' $str ");
		$lovesql = "SELECT $select FROM " . tablename('zb_task_love') . " AS a INNER JOIN " . tablename('zb_task_tasklist') . "  AS b ON a.taskid = b.id INNER JOIN " . tablename('mc_members') . " AS c ON c.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' AND b.`uniacid` ='{$_W['uniacid']}' AND c.`uniacid` ='{$_W['uniacid']}' $str ORDER BY a.`id` DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		$loveinfo = pdo_fetchall($lovesql);
		$pager = pagination($total, $pindex, $psize);
		return array($loveinfo,$pager);
	}	
	
	//查询单条
	static function getSingleLove($taskid,$uid){
		global $_W;
		$loveinfo = pdo_fetch("SELECT * FROM " . tablename('zb_task_love') . " WHERE `uniacid` ='{$_W['uniacid']}' AND `taskid` = '{$taskid}' AND `uid` ='{$uid}' ");
		return $loveinfo;
	}


	
}
	
?>