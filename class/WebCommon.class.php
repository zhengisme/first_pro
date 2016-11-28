<?php 

class WebCommon
{
	
	//禁止浏览和恢复浏览回复 $isscan=1是禁止浏览，0是恢复浏览
	static function isScanInTaskAndTaskinfo($isscan,$id){
		global $_W;
		$res = pdo_update('zb_task_reply',array('isscan'=>$isscan),array('uniacid'=>$_W['uniacid'],'id'=>$id));
		return $res;
	}
	
	
	
	//查询提现记录、提取保证金记录
	static function getDrwmoneyLogAndDepositLog($_GPC,$from){
		if($_GPC['op'] == 'waitpay') $where['status'] = '0';
		if($_GPC['op'] == 'payed') $where['status'] = '1';
		if($_GPC['op'] == 'back') $where['status'] = '2';
		if($_GPC['op'] == 'refuse') $where['status'] = '3';		
		
		
		$order = $_GPC['order'];
		if(empty($order))  $order = 'id';
		$money = model_drwmoney::getAllDrwmoneyLogAndDepositLog('zb_task_'.$from,$where,$order,10,$_GPC['page']);
		return $money;
	}
	
	
	//循环删除数据
	static function deleteDataInWeb($arrayid,$tablename){
		global $_W;
		$successnum = 0;
		$failnum = 0;
		foreach($arrayid as $k=>$v){	
			if($tablename == 'zb_task_privatetask'){ //删除私包任务，给雇主退钱
				$privateinfo = model_privatetask::getSinglePrivateTaskNoInner(array('id'=>$v));
				if(in_array($privateinfo['status'],array(2,3,7,9)) || ($privateinfo['status'] == 0 && $privateinfo['type'] == '2')){				
					//退资金
					$res = model_privatetask::backMoneyToBossInPrivateTask($privateinfo);					
				}
			}
			
			$res = Util::deleteData($v,$tablename);
			if($res) {
				//如果是任务列表删除回复数据zb_task_tasklist
				if($tablename == 'zb_task_tasklist')
				pdo_delete('zb_task_reply',array('uniacid'=>$_W['uniacid'],'taskid'=>$v),'AND');
				//如果是删除回复，给任务增加剩余票数
				if($tablename == 'zb_task_reply'){
					model_task::updateTaskTable(array('lastnumber'=>1),$id=0);
				}
				$successnum ++ ;
			}else{
				$failnum ++;
			}
		}
		return array($successnum,$failnum);
	}
	
	//查询回复记录
	static function getReplyInUserinfoAndTask($_GPC,$from){
		
		if($from == 'userinfo') $where['uid'] = $_GPC['uid'];
		$where['status'] = $_GPC['status'];
		if(empty($where['status'])) unset($where['status']);
		
		$where['isscan'] = $_GPC['isscan'];
		if(!isset($_GPC['isscan'])) unset($where['isscan']);
		
		$reply = model_reply::getAllTaskReply($where,$_GPC['page']);
		$replyinfo = $reply[0];
		foreach($replyinfo as $k=>$v){
			$replyinfo[$k]['images'] = iunserializer($replyinfo[$k]['images']);
		}
		return array($replyinfo,$reply[1]);
	}
	
	//查询支付记录
	static function getPayLogInUserinfoAndMoneylog($_GPC,$from){
		if($from == 'userinfo') $where['uid'] = intval($_GPC['uid']);
		
		$where['status'] = intval($_GPC['status']);		
		if(!isset($_GPC['status'])) unset($where['status']);
		
		$order = empty($_GPC['order'])?'id':$_GPC['order'];
		
		$pay = Util::getAllData('zb_task_paylog',$where,$_GPC['page'],10,$order);
		return $pay;
	}
	
	//查询保证金记录
	static function getDepositLogInUserinfoAndMoneylog($_GPC,$from){
		global $_W;
		if($from == 'userinfo') $where['uid'] = intval($_GPC['uid']);
		
		$where['type'] = intval($_GPC['type']);	
		if(empty($where['type'])) unset($where['type']);

 		if($from == 'deposit' && $_GPC['op'] == 'add') $where['type'] = 1;
		if($from == 'deposit' && $_GPC['op'] == 'admin') $where['type'] = 3;
		
		$order = empty($_GPC['order'])?'id':$_GPC['order'];
		
		$deposit = Util::getAllData('zb_task_depositlog',$where,$_GPC['page'],10,$order);
		return $deposit;
	}
	

	//查询余额记录
	static function getCredit2LogInUserinfoAndMoneylog($_GPC,$from){
		global $_W;
		if($from == 'userinfo') $where['uid'] = intval($_GPC['uid']);
		$where['type'] = intval($_GPC['type']);
		if(empty($where['type'])) unset($where['type']);
		
		$order = empty($_GPC['order'])?'id':$_GPC['order'];
		
		$money = model_moneylog::getAllMoneyLog('zb_task_moneylog',$where,$_GPC['page'],$order);
		return $money;
	}
	
	//查询非私包任务列表
	static function getTaskList($_GPC,$from){
		global $_W;
		$tasksort = model_tasksort::getAllTasksort(1,200,'app');
		
		if($from == 'userinfo') $where['uid'] = intval($_GPC['uid']);
		
		$where['status'] = intval($_GPC['status']);
		if(empty($_GPC['status'])) unset($where['status']);
		
		$isurg = intval($_GPC['isurg']);
		if($isurg == 1) $where['urgmoney<'] = 0.01;
		if($isurg == 2) $where['urgmoney>'] = 0.01;
		
		$where['typeid'] = intval($_GPC['sort']);
		if(empty($_GPC['sort'])) unset($where['typeid']);
		
		$where['istop'] = intval($_GPC['istop']);
		if(!isset($_GPC['istop'])) unset($where['istop']);
		
		if($_GPC['puber'] == 'admin') $where['uid'] = 0;
		if($_GPC['puber'] == 'user') $where['uid>'] = 1;
		
		$order = $_GPC['orderby'];
		if(empty($order)) $order = 'id';
		
		if($_GPC['op'] == 'search'){ //搜索
			$where = $_GPC['for'];
			$task = model_task::getAllTask($where,$order,10,$_GPC['page'],2,'web');
		}else{
			$task = model_task::getAllTask($where,$order,10,$_GPC['page'],1,'web');
		}
	
		return array($tasksort,$task);
	}
	
	
	static function getPrivateTaskList($_GPC,$op){
		global $_W;		
		
		if($op == 'pubprivatetask') $where['pubuid'] = $_GPC['uid'];
		if($op == 'acceptprivatetask') $where['acceptuid'] = $_GPC['uid'];
		$where['status'] = $_GPC['status'];
		if(!isset($where['status'])) unset($where['status']);
		
		$order = $_GPC['orderby'];
		if($order == 'lasttime') $order = 'overtime7,a.overtime3,a.overtime2,a.overtime0';
		if(empty($order)) $order = 'id';
		
		$task = model_privatetask::getAllPrivateTaskWithBothInfo($where,$order,$_GPC['page']);
		return $task;		
	}
	

	//提现管理页面和提取保证金页面方法 op是页面分页，from是drwmoney和deposit
	static function dealMoneyAndDepositInDrwmoneyAndDeposit($tablename,$_GPC,$op,$from,$module){
		global $_W;			
		$id = $_GPC['checkbox'];
		$success_num =0;
		$fail_num =0;
		$reason = $_GPC['reason'];
		
		foreach($id as $k=>$value){
			if($op == 'recover') $res = Util::updateSingleData($tablename,array('status'=>0,'dealtime'=>'','backreason'=>0),array('id'=>$value,'status'=>3)); //恢复到提现列表
			if($op == 'refusepay') $res = Util::updateSingleData($tablename,array('status'=>3,'dealtime'=>time(),'backreason'=>$reason),array('id'=>$value,'status'=>0)); //拒绝支付
			
			
			if($op == 'toback'){ //退回提现
				if($from == 'drwmoney'){
					$paymoney = Util::getSingleData($tablename,array('id'=>$value,'status'=>0));
					$res = Util::updateSingleData($tablename,array('status'=>2,'dealtime'=>time(),'backreason'=>$reason),array('id'=>$value,'status'=>0));
					if($res){
						//加钱
						$res = model_user::updateUserCredit2($paymoney['uid'],$paymoney['money']);
						model_moneylog::insertMoneyLog($paymoney['uid'],$paymoney['openid'],'',$reason,$paymoney['money'],11);	
						model_user::deleteUserCache($paymoney['openid']);  //删除用户缓存
					}	
				}elseif($from == 'deposit'){
					$paymoney = Util::getSingleData($tablename,array('id'=>$value,'status'=>0));
					$res = Util::updateSingleData($tablename,array('status'=>2,'dealtime'=>time(),'backreason'=>$reason),array('id'=>$value,'status'=>0));
					if($res){
						//加保证金
						$res = Util::addAndMinusData('zb_task_user',array('deposit'=>$paymoney['money']),array('uid'=>$paymoney['uid']));
						if($res) $res = model_depositlog::insertDepositlogData(array('openid'=>$paymoney['openid'],'uid'=>$paymoney['uid']),$paymoney['money'],4,$reason);	//已删缓存
					}
				}
			}
			
			if($op == 'payall'){ //支付
				$paymoney = Util::getSingleData($tablename,array('id'=>$value,'status'=>0));

				$arr['fee'] = $paymoney['money'];//金额
				$arr['openid'] = $paymoney['openid'];
				$WeixinPay = new WeixinPay;		
				$res = $WeixinPay->sendMoneyToUser($arr);	

				if($res['result_code'] == 'SUCCESS'){
					$success_num ++;
					$res = Util::updateSingleData($tablename,array('status'=>1,'dealtime'=>time()),array('id'=>$value,'status'=>0));
					Message::emessage($paymoney['openid'],$module,$arr['fee'],$from); //发送提现通知
					
				}else{
					$fail_num ++;
				}
				if($res['result_code']=='FAIL'){
					var_dump($res);
				}
			}
			
			if($op != 'payall'){
				if($res){
					$success_num ++;
				}else{
					$fail_num ++;
				}
			}
			
		}
//		message('操作完成，成功'.$success_num.'人,失败'.$fail_num.'人', referer(), 'success');
	}
	
}


?>