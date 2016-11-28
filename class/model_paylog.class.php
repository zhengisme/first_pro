<?php 
	
class model_paylog
{
	
	//查询单条数据
	static function getSinglePaylog($array){
		global $_W;
		$str = '';
		foreach($array as $k=>$v){
			$str .= ' AND `'.$k.'` = \''.$v.'\' ';
		}
		$sql = "SELECT * FROM ".tablename('zb_task_paylog') . " WHERE `uniacid` = '{$_W['uniacid']}' $str";
		return pdo_fetch($sql);
	}
	
	
	
}
	
?>