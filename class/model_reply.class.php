<?php 
	
class model_reply
{
	
	//自动处理需要的查询所有对应的回复
	static function autoDealNeadTheReplyFunc($where){
		global $_W;
		$str = '';
		foreach((array)$where as $k=>$v ){
			$str .= ' AND `'.$k.'` = '.$v.' ';
		}		
		$replyinfo = pdo_fetchall("SELECT * FROM " . tablename('zb_task_reply') . " WHERE `uniacid` ='{$_W['uniacid']}' $str ORDER BY `id` DESC ");		
		return $replyinfo;
	}
	
	//更新回复状态及时间,收益
	static function updateReplyStatus($id,$status){
		global $_W;
		$id= intval($id);
		$res = pdo_update('zb_task_reply',array('status'=>$status,'dealtime'=>time()),array('id' => $id,'uniacid' => $_W['uniacid']));
		if($res) return true;
		return false;
	}	
	
	//查询单条回复
	static function getSingleReply($array){
		global $_W;		
		$str = '';
		foreach((array)$array as $k=>$v ){
			$str .= ' AND `'.$k.'` = '.$v.' ';
		}
		$replyinfo = pdo_fetch("SELECT * FROM " . tablename('zb_task_reply') . " WHERE `uniacid` ='{$_W['uniacid']}' $str");
		return $replyinfo;	
	}	
	
	//批量查询回复
	static function getAllTaskReply($array,$page,$num=10){
		global $_W;			
		$pindex = max(1, intval($page));
		$psize = $num;
		
		$select = 'a.*,b.nickname,b.credit2,b.avatar';

		$str = '';
		foreach((array)$array as $k=>$v ){
			$str .= ' AND a.`'.$k.'` = '.$v.' ';
		}
		
		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename('zb_task_reply') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ");
		
		$replyinfo = pdo_fetchall("SELECT $select FROM " . tablename('zb_task_reply') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY a.`id` DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$pager = pagination($total, $pindex, $psize);			
		return array($replyinfo,$pager);
	}	
	
	//批量查询我执行的
	static function getMyTaskReply($array,$page){
		global $_W;			
		$pindex = max(1, intval($page));
		$psize = 10;	
		
		$select = 'b.*,c.nickname,c.credit2,c.avatar';

		$str = '';
		foreach((array)$array as $k=>$v ){
			$str .= ' AND a.`'.$k.'` = '.$v.' ';
		}
	
		$total = pdo_fetchcolumn("SELECT distinct COUNT(a.id) FROM " . tablename('zb_task_reply') . " AS a LEFT JOIN " . tablename('zb_task_tasklist') . "  AS b ON a.taskid = b.id LEFT JOIN " . tablename('mc_members') . " AS c ON c.uid = a.uid WHERE a.`uniacid` ='{$_W['uniacid']}' AND b.`uniacid` ='{$_W['uniacid']}' AND c.`uniacid` ='{$_W['uniacid']}' $str ");
		$replyinfo = pdo_fetchall("SELECT distinct $select FROM " . tablename('zb_task_reply') . " AS a LEFT JOIN " . tablename('zb_task_tasklist') . "  AS b ON a.taskid = b.id LEFT JOIN " . tablename('mc_members') . " AS c ON c.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' AND b.`uniacid` ='{$_W['uniacid']}' AND c.`uniacid` ='{$_W['uniacid']}' $str ORDER BY a.`id` DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$pager = pagination($total, $pindex, $psize);			
		return array($replyinfo,$pager);
	}

	/**
	 * 任务详情回答人列表
	 * @param $array
	 * @param $page
	 * @param int $num
	 */
	static function getAllTaskAnswer($array,$page,$num=10){
		global $_W;
		$pindex = max(1, intval($page));
		$psize = $num;

		$select = 'a.*,b.nickname,b.credit2,b.avatar';

		$str = '';
		foreach((array)$array as $k=>$v ){
			$str .= ' AND a.`'.$k.'` = '.$v.' ';
		}

		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename('zb_task_reply') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ");

		$answerinfo = pdo_fetchall("SELECT $select FROM " . tablename('zb_task_reply') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY a.`id` DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

		$pager = pagination($total, $pindex, $psize);
		return array($answerinfo,$pager);
	}

	static function getAllReportSection($array,$page,$num=20){
		global $_W;
		$pindex = max(1, intval($page));
		$psize = $num;

		$select = 'a.*,b.nickname,b.credit2,b.avatar';

		$str = '';
		foreach((array)$array as $k=>$v ){
			$str .= ' AND a.`'.$k.'` = '.$v.' ';
		}

		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename('zb_task_section') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ");

		$answerinfo = pdo_fetchall("SELECT $select FROM " . tablename('zb_task_section') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY a.`id` DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

		$pager = pagination($total, $pindex, $psize);
		return array($answerinfo,$pager);
	}
	
}
	
	
?>