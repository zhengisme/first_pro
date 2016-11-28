<?php 

/*
	用户表类
*/
class model_user 
{	
	
	
	//批量查询用户
	static function getAllUser($wherearray,$order,$page=1,$type=1,$seq='DESC'){
		global $_W;
		$pindex = max(1, intval($page));
		$psize = 20;
		
		if($type == 1){
			$str = Util::structWhereString($wherearray,'a');			
		}elseif($type == 2){ //搜索
			$str = ' AND (';
			foreach($wherearray as $k=>$v){
				if($k == 'nickname'){
					$str .= " b.`".$k."` LIKE '%".$v."%' OR";
				}else{
					$str .= " a.`".$k."` LIKE '%".$v."%' OR";
				}
			}
			$str = trim($str,'OR').')';
		}

		if($order == 'credit2') {
			$order = " b.`".$order."` ";
		}else{
			$order = " a.`".$order."` ";
		}
		
		$select = 'a.*,b.nickname,b.credit2,b.avatar';
		
		$total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM " . tablename('zb_task_user') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ");
		
		$userinfo = pdo_fetchall("SELECT $select FROM " . tablename('zb_task_user') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}' $str ORDER BY $order $seq " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$pager = pagination($total, $pindex, $psize);	
		
		return array($userinfo,$pager);
		
	}
	
	//改变用户信誉分数
	static function changeUserCreditScore($num=0,$uid = 0){
		global $_W;	
		if($num == 0) return false;
		$res = pdo_query("UPDATE ".tablename('zb_task_user')." SET `creditscore` = `creditscore` + '{$num}' WHERE uid = :uid AND uniacid = :uniacid", array(':uid' => $uid,':uniacid' => $_W['uniacid']));
		if($res) return true;
		return false;
	}	
	
	//增加被采纳数量
	static function addUserTableAcceptNumber($replynumber=0,$acceptednum = 0,$uid = 0){
		global $_W;	
		if(($replynumber == 0 && $acceptednum == 0) || $uid == 0) return false;
		$res = pdo_query("UPDATE ".tablename('zb_task_user')." SET `replynumber` = `replynumber` + '{$replynumber}',`acceptednumber` = `acceptednumber` + '{$acceptednum}' WHERE uid = :uid AND uniacid = :uniacid", array(':uid' => $uid,':uniacid' => $_W['uniacid']));
		if($res) return true;
		return false;
	}	
	
	//增加用户采纳数量
	static function addUserTablePubnumber($pubnumber=0,$acceptnum = 0,$uid = 0){
		global $_W;
		if(($pubnumber == 0 && $acceptnum == 0) || $uid == 0) return false;
		$res = pdo_query("UPDATE ".tablename('zb_task_user')." SET `pubnumber` = `pubnumber` + '{$pubnumber}',`acceptnumber` = `acceptnumber` + '{$acceptnum}' WHERE uid = :uid AND uniacid = :uniacid", array(':uid' => $uid,':uniacid' => $_W['uniacid']));
		if($res) return true;
		return false;
	}	
	
	//删除用户缓存文件
	static function deleteUserCache($openid){
		$userstr = Util::sessionAndCookieUserStr($openid);
		return Util::deleteCache('user',$userstr);
		return false;
	}
	
	//变化用户余额
	static function updateUserCredit2($uid,$money){
		global $_W;
		if($money == 0) return false;
		load() -> model('mc');
		$log = array($uid,'超级任务模块改变余额','zb_task', 0, 0);
		$res = mc_credit_update($uid, 'credit2', $money, $log);
		if($res) return true;
		return false;
	}
	
	//初始化用户数据
	static function initUserInfo(){
		global $_W;
//		$_W['openid'] = 'or3G6tyvJFSj5sLuJloXNs_mQ3lw';//测试环境打开  creditscore
		$userstr = Util::sessionAndCookieUserStr($_W['openid']); //字符串
		$userinfo = Util::getCache('user',$userstr); //查询缓存

		//存在缓存数据直接处理后返回。zgk update 1!=1
		if(!empty($userinfo) && 1!=1){
			if($userinfo['expire'] < time()) $userinfo = self::updateUser($_W['openid']);
			if($userinfo['status'] == 2) die('您的账号已被禁用'); //被拉黑的账户
			//更新登录时间
			if($userinfo['logintime'] < time()-24*3600){
				mc_oauth_userinfo($_W['uniacid']);
				$data = array('logintime' => time());
				pdo_update('zb_task_user', $data, array('id' => $userinfo['id']));				
			}

		}else{	
			//如果不存在,先查询用户是否已存在，如果不存在就更新，如果存在 设置加密存储cookie后返回，有效期24小时		
			$userinfo = self::getSingleUserInfo(array('openid'=>$_W['openid']));

			if (empty($userinfo['logintime']) && empty($userinfo)) {
				load() -> model('mc');
				unset($_SESSION['userinfo']); //删除缓存，防止返回缓存 mc_fansinfo 里有可能返回缓存的session数据
				$oauthinfo = mc_fansinfo($_W['openid']);
				
				//如果后台关闭了自动注册，关注后member表不会自动增加数据。所以需要向member表内插入数据，不然uid=0会导致出错。
				//如果后台开启了自动注册，那么后面这段代码不会执行。
				if(empty($oauthinfo)) self::alertWechatLogin(); //如果没有粉丝信息提示关注
				if(empty($oauthinfo['uid'])){ //存在粉丝信息，但是没有uid(member)数据
					mc_update($_W['openid'], array('email'=>md5($_W['openid']).'@we7.cc','nickname'=>$oauthinfo['nickname'],'avatar'=>$oauthinfo['avatar']));//更新数据 目的是往member内插入数据。
				}
				$oauthinfo = mc_fansinfo($_W['openid']); //再次查询数据
				
				if(empty($oauthinfo['uid'])) self::alertWechatLogin();
				$data = array(
					'uniacid' => $_W['uniacid'],
					'openid' => $_W['openid'],
					'uid' => $oauthinfo['uid'],
					'city' => $oauthinfo['city'],
					'logintime' => time()
				);
				pdo_insert('zb_task_user', $data);
			}
			$userinfo = self::updateUser($_W['openid']);
		}

		if($userinfo['state'] == 2){
			if(empty($userinfo['guydesc'])){
				$userinfo['guydesc'] = ["pic"=>$userinfo["avatar"],'manuname'=>$userinfo["nickname"]];
			}else{
				if(!is_array($userinfo['guydesc'])){
					$userinfo['guydesc'] = json_decode($userinfo['guydesc'],true);
					$userinfo['avatar'] = $userinfo['guydesc']['pic'];
					$userinfo['nickname'] = $userinfo['guydesc']['manuname'];
				}
			}
		}

		return $userinfo;
	}
	//更新用户信息
	static function updateUser($openid){
		$userstr = Util::sessionAndCookieUserStr($openid); 	
		$userinfo = self::getSingleUserInfo(array('openid'=>$openid));		
		if(empty($userinfo)) die('出现异常');
		
		$userinfo['expire'] = time()+1800; //过期时间
		//$cacheinfo = iserializer($userinfo);
		$res = Util::setCache('user',$userstr,$userinfo);
		
		return $userinfo;
	}
	
	//查询一条用户数据,
	static function getSingleUserInfo($where){
		global $_W;	
		$str = Util::structWhereString($where,'b');	
		$select = 'a.nickname,a.credit2,a.avatar,b.*';
		$selectsql = "SELECT $select FROM " . tablename('mc_members') . " AS a INNER JOIN " . tablename('zb_task_user') . " AS b ON a.`uid` = b.`uid`   WHERE b.`uniacid` ='{$_W['uniacid']}' $str";
		$userinfo = pdo_fetch($selectsql);

		if($userinfo['state'] == 2){
			if(empty($userinfo['guydesc'])){
				$userinfo['guydesc'] = ["pic"=>$userinfo["avatar"],'manuname'=>$userinfo["nickname"]];
			}else{
				if(!is_array($userinfo['guydesc'])){
					$userinfo['guydesc'] = json_decode($userinfo['guydesc'],true);
					$userinfo['avatar'] = $userinfo['guydesc']['pic'];
					$userinfo['nickname'] = $userinfo['guydesc']['manuname'];
				}
			}
		}
		return $userinfo;	
	}
	
	//非微信端提示
	static function alertWechatLogin(){
		global $_W;
		$qrcode = tomedia('qrcode_'.$_W['acid'].'.jpg');
		die("<!DOCTYPE html>
            <html><head><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
            </head>
            <body>
            <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请用微信扫以下二维码打开链接</h4><br><img width='200px' src='".$qrcode."'></div></body></html></div></div></div>
            </body></html>");
	}


	/**
	 * 更新用户信息
	 * @param $mobileNum
	 * @param int $state
	 * @return array
	 */
	static function updateMemberInfo($mobileNum, $state = 1,$guydesc)
	{
		global $_W;
		medoo()->update('zb_task_user',
			['state' => $state, 'mobile' => $mobileNum,'guydesc'=>$guydesc,],
			[
				'AND' => ['uniacid' => $_W['uniacid'], 'openid' => $_W['openid'],],
			]
		);
		return ['code' => 0, 'message' => '更新成功'];
	}
	
}
	
	
	
?>