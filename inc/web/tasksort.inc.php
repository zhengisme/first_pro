<?php 
	global $_GPC,$_W;
	$op = empty($_GPC['op'])?'display':$_GPC['op'];
	
	//批量删除
	if(checksubmit('delete')){
		$res = WebCommon::deleteDataInWeb($_GPC['checkbox'],'zb_task_tasksort');
		message('操作完成,成功删除'.$res[0].'项，失败'.$res[1].'项',referer(),'success');
	}
	
	//编辑
	if(checksubmit()){
		$_GPC = Util::trimWithArray($_GPC);
		$data['name'] = $_GPC['tasksortname'];
		$data['order'] = $_GPC['tasksortorder'];		
		$data['time'] = time();
		$data['uniacid'] = $_W['uniacid'];
		
		if(empty($_GPC['id'])){
			$res = pdo_insert('zb_task_tasksort',$data);
		}else{
			$res = pdo_update('zb_task_tasksort',$data,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
		}

		if($res) {
			Util::deleteCache('tasksort','tasksort'); //删除缓存	
			message('操作成功',$this->createWebUrl('tasksort',array('op'=>'display')),'success');		
		}
		message('操作失败了');
	}
	
	//批量排序编辑
	if(checksubmit('ordersub')){
		foreach($_GPC['order'] as $k=>$v){
			pdo_update('zb_task_tasksort', array('order' => $v,'name'=>$_GPC['name'][$k]), array('id' => $k,'uniacid'=>$_W['uniacid']));
		}
		//删除缓存
		Util::deleteCache('tasksort','tasksort'); //删除缓存
		message('操作成功',$this->createWebUrl('tasksort',array('op'=>'display')),'success');
	}
	
	
	if($op == 'edit'){
		$taskinfo = model_tasksort::getSingleTsksort($_GPC['id']);
	}

	if($op == 'display'){
		$tasksortlist = model_tasksort::getAllTasksort($_GPC['page'],10,'web');
		$tasksortinfo = $tasksortlist[0];
		$pager = $tasksortlist[1];
	}	
	
	include $this->template('web/tasksort');
?>