<?php 
	global $_GPC,$_W;
	$op = empty($_GPC['op'])?'display':$_GPC['op'];
	
	
	//批量删除
	if(checksubmit('delete')){
		$res = WebCommon::deleteDataInWeb($_GPC['checkbox'],'zb_task_guysort');
		message('操作完成,成功删除'.$res[0].'项，失败'.$res[1].'项',referer(),'success');
	}	
	
	
	if(checksubmit()){
		$_GPC = Util::trimWithArray($_GPC);
		$data['title'] = $_GPC['guysorttitle'];
		$data['desc'] = $_GPC['guysortdesc'];		
		$data['order'] = $_GPC['guysortorder'];
		$data['time'] = time();
		$data['uniacid'] = $_W['uniacid'];
		
		if(empty($_GPC['id'])){
			$res = pdo_insert('zb_task_guysort',$data);
		}else{
			$res = pdo_update('zb_task_guysort',$data,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
		}

		if($res) {
			Util::deleteCache('guysort','guysort'); //删除缓存	
			message('操作成功',$this->createWebUrl('guysort',array('op'=>'display')),'success');		
		}
		message('操作失败了');
	}
	
	//批量排序编辑
	if(checksubmit('ordersub')){
		foreach($_GPC['order'] as $k=>$v){
			pdo_update('zb_task_guysort', array('order' => $v,'title'=>$_GPC['title'][$k],'desc'=>$_GPC['desc'][$k]), array('id' => $k,'uniacid'=>$_W['uniacid']));
		}
		//删除缓存
		Util::deleteCache('guysort','guysort'); //删除缓存
		message('操作成功',$this->createWebUrl('guysort',array('op'=>'display')),'success');
	}
	
	
	if($op == 'edit'){
		$guysortinfo = model_guysort::getSingleGuysort($_GPC['id']);
	}
	
	if($op == 'display'){
		$guysortlist = model_guysort::getAllGuysort($_GPC['page'],10,'web');
		$guysortinfo = $guysortlist[0];
		$pager = $guysortlist[1];
	}	
	
	include $this->template('web/guysort');
?>