<?php 
	global $_GPC,$_W;
	
	$aboutus = Util::getSingleData('zb_task_aboutus',array('uniacid'=>$_W['uniacid']));	
	
	if(checksubmit()){
		$data['content'] = $_GPC['content'];
		$data['uniacid'] = $_W['uniacid'];
		
		if(empty($aboutus)){
			$res = pdo_insert('zb_task_aboutus',$data);
		}else{
			$res = pdo_update('zb_task_aboutus',$data,array('uniacid'=>$_W['uniacid']));
		}
		Util::deleteCache('aboutus','aboutus');
		if($res) message('更新成功',referer(),'success');
		
	}
	

	
	
	include $this->template('web/aboutus');
?>