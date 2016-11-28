<?php 
	
class model_tasksort
{
	
	
	
	
	
	//查询单个
	static function getSingleTsksort($id){
		global $_W;		
		$sql = "SELECT * FROM ".tablename('zb_task_tasksort') . " WHERE uniacid = :uniacid AND id = :id";
		return pdo_fetch($sql,array(
			':uniacid'=> $_W['uniacid'],
			':id' => $id
		));
		
	}
	
	//批量查询
	static function getAllTasksort($page,$num,$from){
		global $_W;
		
		if($from == 'app'){
			$tasksortinfo = Util::getCache('tasksort','tasksort');
			if(!empty($tasksortinfo)) return $tasksortinfo;
		}
		
		$pindex = max(1, intval($page));
		$psize = $num;
		$total = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('zb_task_tasksort') . " WHERE `uniacid` ='{$_W['uniacid']}'");		
		$tasksortinfo = pdo_fetchall("SELECT * FROM " . tablename('zb_task_tasksort') . " WHERE `uniacid` ='{$_W['uniacid']}' ORDER BY `order` ASC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		$pager = pagination($total, $pindex, $psize);
		
		if($from == 'app'){
			Util::setCache('tasksort','tasksort',$tasksortinfo);
			return $tasksortinfo;
		}
		
		return array($tasksortinfo,$pager);
	}
	
	
}
	
	
?>