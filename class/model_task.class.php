<?php 
	
class model_task
{
	
	//管理员审核通过任务
	static function verifyTaskWithPass($module,$taskid){
		$overtime = time() + $module->module['config']['autoconfirm']*3600;
		$res = pdo_update('zb_task_tasklist',array('status'=>1,'overtime'=>$overtime),array('id'=>$taskid));
		return $res;
	}
	//管理员审核不通过
	static function verifyTaskWithNoPass($module,$taskinfo){
		$res = pdo_update('zb_task_tasklist',array('status'=>4),array('id'=>$taskinfo['id']));
		$moneylog = model_moneylog::getAllMoneyLog('zb_task_moneylog',array('taskid'=>$taskinfo['id']),1);
		foreach($moneylog[0] as $kk=>$vv){
			$res = model_user::updateUserCredit2($vv['uid'],-$vv['money']); //退钱			
			if($res) $res = model_moneylog::insertMoneyLog($vv['uid'],$vv['openid'],$taskinfo['id'],$taskinfo['title'],-$vv['money'],13);
		}
		model_user::deleteUserCache($taskinfo['openid']);//删除用户缓存
		//发通知
		return $res;
	}	
	
	
	//采纳和拒绝回复在ajaxdeal和web/taskinfo内使用
	static function acceptRplyAndRefuseReply($_GPC,$from,$uid=0,$module){
		global $_W;		
	
		$data['replyid'] = intval($_GPC['replyid']);
		$data['taskid'] = intval($_GPC['taskid']);
		$type = $_GPC['type'];
		$taskinfo = $taskinfo = medoo()->get('zb_task_tasklist','*',['id'=>$data['taskid']]);
		$replyinfo = model_reply::getSingleReply(array('id'=>$data['replyid']));

		if($taskinfo['id'] != $replyinfo['taskid'] || $taskinfo['status'] != 1 || empty($taskinfo) || empty($replyinfo) || $replyinfo['status'] != 1 ) return false;
//		if($from == 'app' && ($taskinfo['uid'] != $uid)) return false;
		if($type == 'accept'){

			if($replyinfo["status"] >= 2){
				return  array('status'=>2,'errmsg'=>'回答已被采纳');
			}
			if($taskinfo["status"] >= 2){
				return  array('status'=>2,'errmsg'=>'任务已结束');
			}
			if($taskinfo["lastnumber"] == 0){
				return  array('status'=>2,'errmsg'=>'已经采纳'.$taskinfo["number"].'次');
			}
			$res = self::acceptReplyAccountInDealreplyAndCrontab($taskinfo,$replyinfo,$module); //采纳回复

			//发通知	
			
		}elseif($type == 'refuse'){
			//改变回复状态，
			$res = model_reply::updateReplyStatus($data['replyid'],3); 
			
			//给用户增加发布数量
			if($res) {
				if($taskinfo['uid'] >0) model_user::addUserTablePubnumber(1,0,$taskinfo['uid']);
				model_user::addUserTableAcceptNumber(1,0,$replyinfo['uid']); //被采纳
			}
			//改变任务剩余票数 这里不增加剩余票数，不然加急的次数用完后就不会给以后回复的加加急奖励，会引起矛盾。
			//model_task::updateTaskTable(1,$taskinfo['id']);
			
			//改变用户信誉分数
			if($taskinfo['uid'] >0){
				model_user::changeUserCreditScore(-0.6,$taskinfo['uid']);
				model_scorelog::insertScoreLog($taskinfo['uid'],$taskinfo['openid'],-0.6,1); //插入积分记录				
			}
			
			model_user::changeUserCreditScore(-1,$replyinfo['uid']);
			model_scorelog::insertScoreLog($replyinfo['uid'],$replyinfo['openid'],-1,2); //插入积分记录
		
		}
		if($taskinfo['uid'] >0) model_user::deleteUserCache($taskinfo['openid']);//删除用户缓存
		model_user::deleteUserCache($replyinfo['openid']);//删除用户缓存
		return  array('status'=>1,'errmsg'=>'success');
	}
	
	//检验是否存在没有完成的任务，在提取保证金时用到。
	static function issetTasking($uid){
		global $_W;
		$taskinfo = Util::getSingleData('zb_task_tasklist',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'status'=>1));
		if(!empty($taskinfo)) return true;return false;
	}
	
	//任务发布者结算任务，自动任务页面结算任务。
	static function accountTaskInAjaxdealAndCrontab($taskinfo,$module){
		global $_W;
		$currtime = time();
		//变为已结束
		$res = Util::updateSingleData('zb_task_tasklist',array('status'=>2,'contime'=>$currtime,'overtime'=>$currtime),array('id'=>$taskinfo['id']));
		if($res){
			//处理没有采纳的（采纳、发钱）
			$replyinfo = model_reply::autoDealNeadTheReplyFunc(array('taskid'=>$taskinfo['id'],'status'=>1));
			foreach($replyinfo as $kk=>$vv){		
//				self::acceptReplyAccountInDealreplyAndCrontab($taskinfo,$vv,$module);  //采纳回复
				model_user::deleteUserCache($vv['openid']);//删除回复用户缓存
			}
			if($taskinfo['uid'] > 0){ //退钱	
				//重新查询任务，因为上面处理后加急剩余数量已经变化了。需要重新获取数据
				$newv = Util::getSingleData('zb_task_tasklist',array('id'=>$taskinfo['id'])); 
				
				/*//这里是计算拒绝的回复应退的金额
				$refusemoney = pdo_fetchcolumn(" SELECT SUM(`income`) FROM " . tablename('zb_task_reply') . " WHERE `uniacid` = {$_W['uniacid']} AND `taskid` = '{$taskinfo['id']}' AND `status` = 3");*/
				
				$backmoney = $newv['money']*$newv['lastnumber'] + $newv['urgmoney']*$newv['urgnumber'] ;
				$res = model_user::updateUserCredit2($newv['uid'],$backmoney); //发放金额，			
				if($res) $res = model_moneylog::insertMoneyLog($newv['uid'],$newv['openid'],$newv['id'],$newv['title'],$backmoney,8); //插入记录
				
				model_user::deleteUserCache($newv['openid']);//删除雇主用户缓存
				
				//发通知
			}
			return true;
		}
		return false;
	}	
	
	
	
	//采纳任务 在ajaxdeal的采纳和crontab内用到。
	static function acceptReplyAccountInDealreplyAndCrontab($taskinfo,$replyinfo,$module){
		$addmoney = $replyinfo['income'];

		//修改剩余打赏数
		if($taskinfo["lastnumber"] > 0){
			medoo()->update('zb_task_tasklist',["lastnumber"=>($taskinfo["lastnumber"]-1)],['id'=>$taskinfo["id"]]);
		}
		//改变回复状态，
		$res = model_reply::updateReplyStatus($replyinfo['id'],2);
		//发放金额，
		if($res) $res = model_user::updateUserCredit2($replyinfo['uid'],$addmoney);
		//插入记录
		if($res) $res = model_moneylog::insertMoneyLog($replyinfo['uid'],$replyinfo['openid'],$taskinfo['id'],$taskinfo['title'],$addmoney,4);
		//给用户增加采纳 被采纳 ，信誉积分
		if($res) {
			if($taskinfo['uid'] >0) model_user::addUserTablePubnumber(1,1,$taskinfo['uid']);
			model_user::addUserTableAcceptNumber(1,1,$replyinfo['uid']); //被采纳
			//信誉积分
			if($taskinfo['uid'] >0) model_user::changeUserCreditScore(0.2,$taskinfo['uid']);
			if($taskinfo['uid'] >0) model_scorelog::insertScoreLog($taskinfo['uid'],$taskinfo['openid'],0.2,3); //插入积分记录
			model_user::changeUserCreditScore(0.2,$replyinfo['uid']);
			model_scorelog::insertScoreLog($replyinfo['uid'],$replyinfo['openid'],0.2,4); //插入积分记录
			Message::fmessage($replyinfo['openid'],$module,$addmoney,$taskinfo['title'],$taskinfo['id']);//发通知
			return true;
		}
		return false;
	}	
	
	
	//自动处理需要的查询
	static function autoDealNeadTheFunc($wherearray,$num=10){
		global $_W;			
		$taskinfo = Util::getAllData('zb_task_tasklist',$wherearray,1,$num);
		return $taskinfo;
	}
	
	//更新任务表剩余票数
	static function updateTaskTable($array,$id=0){
		global $_W;	
		$res = Util::addAndMinusData('zb_task_tasklist',$array,array('id' => $id));
		if($res) return true;
		return false;
	}
	
	//减1个任务加急票数
	static function minusOneTaskUrgNumber($taskid){
		$res = Util::addAndMinusData('zb_task_tasklist',array('urgnumber'=>-1),array('id'=>$taskid));
		return $res;
	}
	
	//查询单条任务
	static function getSingleTask($id){
		$id = intval($id);
		global $_W;	
		$select = 'a.nickname,a.credit2,a.avatar,b.deposit,b.mobile,b.qrcode,b.pubnumber,b.acceptnumber,c.*';
		$sql = "SELECT $select FROM " . tablename('mc_members') . " AS a RIGHT JOIN " . tablename('zb_task_user') . " AS b ON a.uid = b.uid RIGHT JOIN " . tablename('zb_task_tasklist') . " AS c ON b.uid = c.uid  WHERE c.uniacid ='{$_W['uniacid']}' AND c.id ='{$id}'";
		$taskinfo = pdo_fetch($sql);
		return $taskinfo;
	}
	
	//批量查询任务数据 type 1是默认的查询，2是搜索
	static function getAllTask($wherearray,$order='a.id',$num=10,$page=1,$type=1,$from){
		global $_W;
		$pindex = max(1, intval($page));
		$psize = $num;
		
		if($type == 1){
			$str = Util::structWhereString($wherearray,'a');			
		}elseif($type == 2){
			$str = " AND `title` LIKE '%".$wherearray."%' ";
		}

		
		$select = 'a.*,b.nickname,b.credit2,b.avatar,c.deposit';
		
		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename('zb_task_tasklist') . " AS a LEFT JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid LEFT JOIN " . tablename('zb_task_user') . " AS c ON c.uid = a.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ");
		
		$taskinfo = pdo_fetchall("SELECT $select FROM " . tablename('zb_task_tasklist') . " AS a LEFT JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid LEFT JOIN " . tablename('zb_task_user') . " AS c ON c.uid = a.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY $order DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$pager = pagination($total, $pindex, $psize);
		
		return array($taskinfo,$pager);
		
	}
	
	//处理发布表单
	static function publishTask($from,$_GPC,$moduel,$type='add'){
		global $_W;
		
		$data['number'] = intval($_GPC['tasknumber']);
		$data['money'] = $_GPC['money'];

		$title = $_GPC['taskexname'].$_GPC['taskvariety'].$_GPC['taskstage'].$_GPC['taskmultiple']."倍".$_GPC['taskway'].$_GPC['taskpurpose'];
		$data['title'] = htmlspecialchars($title);

		$data['city'] = $_GPC['pubcity'];
		$data['pic'] = $_GPC['pic'];
		$data['isshow'] = $_GPC['ishide'];


		$data['cover'] = "../addons/zb_task/public/images/cover/cover-".rand(1, 14).'.jpg';
		$cover = Util::$taskcoverarr[$_GPC['taskpurpose']];
		if($cover['state'] == 1){
			$data['cover'] = "../addons/zb_task/public/images/cover/cover-".$cover['index'].'-'.rand(1, $cover['num']).'.jpg';
		}else{
			$data['cover'] = "../addons/zb_task/public/images/cover/cover-default.jpg";
		}
		
		//检验	
		if($data['number'] == '' || $data['money'] == '' || $data['title'] == '' || $data['money'] <= 0 || $data['number'] <= 0 ) return array('status'=>1);

		//扩展属性
		$extra = [];

		$pubParam = util::$staticpubParam;
		foreach ($pubParam as $key => $val) {
			$extra[$key] = $_GPC[$key];
			$data[$key]= $_GPC[$key];
		}
		$data['extra'] = json_encode($extra);
		$total =$data['money']*$data['number'];
		if( $data['city'] == '') return array('status'=>2);
		if($from == 'app'){
			$userinfo = model_user::getSingleUserInfo(array('openid'=>$_W['openid']));
			if($userinfo['creditscore'] <= 0) return array('status'=>7);; //信誉分数太低
			if($data['money'] < $moduel->module['config']['leasttaskmoney']) return array('status'=>8);//赏金小于规定的值
			if($userinfo['credit2'] < $total) return array('status'=>4);
		};
		$data['uniacid'] = $_W['uniacid'];
		$data['openid'] = ($from == 'app')?$userinfo['openid']:0;
		$data['uid'] = ($from == 'app')?$userinfo['uid']:0;
		$data['lastnumber'] = $data['number'];
		$data['createtime'] = time();
		$data['status'] = 3;
		if($from == 'app'){
			/*$data['overtime'] = time() + $moduel->module['config']['autoconfirm']*3600;*/
			if($moduel->module['config']['isverify'] == 1) $data['status'] = 3; //审核任务
		}
		//TODO f验证余额够不够

		if($total > 0 || $from == 'web') $res = pdo_insert('zb_task_tasklist',$data);
		$insertid = pdo_insertid();
		if($res) {
			if($from == 'app'){
				$res = model_user::updateUserCredit2($userinfo['uid'],-$total); //扣余额 任务费
				if($res) model_moneylog::insertMoneyLog($userinfo['uid'],$userinfo['openid'],$insertid,$data['title'],-$total,1);

				model_user::deleteUserCache($userinfo['openid']);//删除用户缓存

				//给管理员发通知
				if(!empty($moduel->module['config']['adminopenid'])){
					Message::cmessage($moduel->module['config']['adminopenid'],$moduel,$data['title'],'pubtask',$insertid);//发通知
				}
					
			}
			return array('status'=>5,'id'=>$insertid);
		}
		return array('status'=>6);
	}
	
	
	
}
	
	
?>