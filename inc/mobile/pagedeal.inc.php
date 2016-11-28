<?php 
	global $_W,$_GPC;
	$userinfo = model_user::initUserInfo(); //用户信息	
	
	//任务详情页面
	if($_GPC['op'] == 'task'){
		$taskid = intval($_GPC['id']);
		$where['taskid'] = $taskid;

		$answerinfo = model_reply::getAllTaskAnswer($where,$_GPC['page']);
		$taskinfo = model_task::getSingleTask($taskid);
		$task_extra = json_decode( $taskinfo['extra'],true);

		$answerinfo = $answerinfo[0];
		$answerlist = [];

		$uidarr = array_column($answerinfo,'uid');
		$userarr = medoo()->select("zb_task_user","*",["uid"=>$uidarr]);
		$userarr = Util::keyBy($userarr,'uid');

		foreach((array)$answerinfo as $k=> $v){
			$singuser = $userarr[$v["uid"]];
			$v["userstate"] = $singuser["state"];
			if($v["userstate"] ==2 && !empty($singuser['guydesc'])){
				$singuser['guydesc'] = json_decode($singuser['guydesc'],true);
				$v["avatar"] = $singuser['guydesc']['pic'];
				$v["nickname"] = $singuser['guydesc']['manuname'];
			}

			$answer_extra = json_decode( $v['extra'],true);
			$v['inittime'] = date('Y-m-d h:i', $v["time"]);
			//回答所有的贴子
			$v["sectioncount"] = 0;
			$sectionitem = medoo()->select('zb_task_section','*',[
				"AND"=>[
					'uid'=>$v['uid'],
					'uniacid'=>$_W["uniacid"],
					'taskid'=>$v['taskid'],
				],
				'ORDER'=>' time desc ',
				'LIMIT'=>[0,1]
			]);
			if(count($sectionitem)>0){
				$sectionitem = $sectionitem[0];
				$difftime =  $sectionitem['time'] -  $v['time'];
				$inday = intval($difftime/60/60/24);
				$inday<1 ? $inday = 1 :$inday += 1 ;
				$sectionitem["inday"] = $inday; //第几天
				$sectionitem["inittime"] = date('Y-m-d', $sectionitem["time"]);//日期
				$sectionitem["images"] = json_decode($sectionitem["images"],true); //图片array
				$sectionitem["imgsum"] = count($sectionitem["images"]);//图片数

				//获取贴子点赞数
				$sectionitem['likesum'] = medoo()->count("zb_task_like",["sectionid"=>$sectionitem['id']]);
				$sectionitem['replysum']   = medoo()->count("zb_task_comment",["sectionid"=>$sectionitem['id']]);
				$v["sectionitem"] =  $sectionitem;
			}else{
				$difftime =  $v["overtime"] -  time();
				$endday = intval($difftime/60/60/24);
				$v["endday"] = $endday;
			}

			$t = Util::taskStat([
				"type"=>2,
				"objectid"=>$v["id"],
			]);
			$v["pv"] = $t["pv"];

			$v["lovenum"] = medoo()->count("zb_task_like",["AND"=>[
				'objectid'  => $v["id"],
				'sectionid' => 0,
				'type'      => 2,
				'pro'      => 1,]
			]);

			$answerlist[$k] = $v;
		}
		$answerinfo = $answerlist;
		ob_start();
		include $this->template('base/answer_item');
		$str = ob_get_contents();
		ob_end_clean();

	}

	//试验报告列表
	if($_GPC["op"] == 'tasklive'){
		$where = [];
		$answerinfo = model_reply::getAllTaskAnswer($where,$_GPC['page']);
		$answerinfo = $answerinfo[0];
		$answerlist = [];

		$uidarr = array_column($answerinfo,'uid');
		$userarr = medoo()->select("zb_task_user","*",["uid"=>$uidarr]);
		$userarr = Util::keyBy($userarr,'uid');

		foreach((array)$answerinfo as $k=> $v){

			$singuser = $userarr[$v["uid"]];
			$v["userstate"] = $singuser["state"];
			if($v["userstate"] ==2 && !empty($singuser['guydesc'])){
				$singuser['guydesc'] = json_decode($singuser['guydesc'],true);
				$v["avatar"] = $singuser['guydesc']['pic'];
				$v["nickname"] = $singuser['guydesc']['manuname'];
			}


			$answer_extra = json_decode( $v['extra'],true);
			$v['inittime'] = date('Y-m-d h:i', $v["time"]);
			//回答所有的贴子
			$v["sectioncount"] = 0;
			$sectionitem = medoo()->select('zb_task_section','*',[
				"AND"=>[
					'uid'=>$v['uid'],
					'uniacid'=>$_W["uniacid"],
					'taskid'=>$v['taskid'],
				],
				'ORDER'=>' time desc ',
				'LIMIT'=>[0,1]
			]);
			if(count($sectionitem)>0){
				$sectionitem = $sectionitem[0];

				$difftime =  $sectionitem['time'] -  $v['time'];
				$inday = intval($difftime/60/60/24);
				$inday<1 ? $inday = 1 :$inday += 1 ;
				$sectionitem["inday"] = $inday; //第几天

				$sectionitem["inittime"] = date('Y-m-d', $sectionitem["time"]);//日期
				$sectionitem["images"] = json_decode($sectionitem["images"],true); //图片array
				$sectionitem["imgsum"] = count($sectionitem["images"]);//图片数

				//获取贴子点赞数
				$sectionitem['likesum'] = medoo()->count("zb_task_like",["sectionid"=>$sectionitem['id']]);
				$sectionitem['replysum']   = medoo()->count("zb_task_comment",["sectionid"=>$sectionitem['id']]);
				$v["sectionitem"] =  $sectionitem;
			}else{
				$difftime =  $v["overtime"] -  time();
				$endday = intval($difftime/60/60/24);
				$v["endday"] = $endday;
			}

			$t = Util::taskStat([
				"type"=>2,
				"objectid"=>$v["id"],
			]);
			$v["pv"] = $t["pv"];

			$v["lovenum"] = medoo()->count("zb_task_like",["AND"=>[
				'objectid'  => $v["id"],
				'sectionid' => 0,
				'type'      => 2,
				'pro'      => 1,]
			]);

			$answerlist[$k] = $v;

		}
		$answerinfo = $answerlist;
		ob_start();
		include $this->template('base/answer_item');
		$str = ob_get_contents();
		ob_end_clean();
	}
	if($_GPC["op"] == 'taskreport'){
		$replyid = intval($_GPC['id']);
//		$where['replyid'] = $replyid;

		$replyinfo 	  = medoo()->get("zb_task_reply", '*', [ 'id' => $replyid]);
		$taskinfo     = medoo()->get("zb_task_tasklist", '*', ['id' => $replyinfo["taskid"]]);

		$where['uid'] = $replyinfo["uid"];
		$where['taskid'] = $replyinfo["taskid"];
		$sectionarray = model_reply::getAllReportSection($where,$_GPC['page']);
		$sectionarray = $sectionarray[0];
		$sectionlist = [];
		$section_ids = array_column($sectionarray,'id');

		//该粉丝点赞列表
		$dolike = medoo()->select("zb_task_like",'*',[
			'AND' =>[
				'uid' =>$userinfo["uid"],
				'sectionid'=>$section_ids,
				'uniacid'   => $this->conf->uniacid,
				'type'=>2,
				'pro'=>1,
			] ,
		]);
		if(empty($dolike)){
			$dolike = [];
		}else{
			$dolike = array_column($dolike,'id','sectionid');
		}
		$rowinday = 0;
		foreach ($sectionarray as $index => $row) {

			$row["imgs"] = json_decode($row["images"],true); //图片array
			$row["imgsum"] = count($row["imgs"]);//图片数

			$row['extra']     = json_decode($row['extra'], true);
			$row['content'] =  nl2br(html_entity_decode(strip_tags($row['content'])));

			$difftime =  $row['time'] -  $replyinfo['time'];
			$inday = intval($difftime/60/60/24);
			$inday<1 ? $inday = 1 :$inday += 1 ;
			if($rowinday != $inday){
				$rowinday = $inday;
				$row["inday"] = $inday; //第几天
			}

			//贴子点赞列表
			$likelist = medoo()->select("zb_task_like",'*',[
				'AND'=>[
					'sectionid'=>$row['id'],
					'type'=>2,
					'pro'=>1,
				],
				'ORDER'=>'id desc',
				'LIMIT'=>10,
			]);
			$row['likelist'] = $likelist;
			$row['inittime'] = date('Y-m-d h:i', $row["time"]);
			if (count($likelist) < 10) {
				$row['likesum'] = count($likelist);
			} else {
				$total          = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("zb_task_like") . "
											WHERE  sectionid = '{$row['id']}' ORDER BY id ");
				$row['likesum'] = $total;
			}
			//粉丝对帖子是否点赞
			if (empty($dolike[$row['id']])) {
				$row['dolike'] = 0;
			} else {
				$row['dolike'] = 1;
			}

			//评论
			$replysql          = "SELECT id,datato,toname,datafrom,nickname,content FROM " . tablename("zb_task_comment") . " WHERE state = 2  and sectionid = '{$row['id']}' ORDER BY id desc limit 10";
			$replylist         = pdo_fetchall($replysql);


			foreach ($replylist as $rindex => &$replyrow){
				$replyrow['content'] =  nl2br(html_entity_decode($replyrow['content']));
			}
			$row['replylist']  = $replylist;
			$row['replysum']   = count($replylist);

			$sectionlist[$index] = $row;
		}

		ob_start();
		include $this->template('base/report_item');
		$str = ob_get_contents();
		ob_end_clean();
	}
	
	if(in_array($_GPC['op'],array('follow','fans'))){
		$otherid = $_GPC['id'];

		if($_GPC['op'] == "follow"){
			$where = [
				'AND' =>[
					'uid'=>empty($otherid)?$userinfo['uid']:$otherid,
					'uniacid'=>$_W["uniacid"],
				] ,
			];
		}else{
			$where = [
				'AND' =>[
					'fid'=>empty($otherid)?$userinfo['uid']:$otherid,
					'uniacid'=>$_W["uniacid"],
				] ,
			];
		}
		$followlist = [];
		$followarray = medoo()->select("zb_task_follow",'*',$where);
		foreach($followarray as $k => &$v){
			$userid = $_GPC['op'] == "follow" ?$v['fid']:$v['uid'];
			$uwhere = array("uid"=>$userid);

			$v["userinfo"] = model_user::getSingleUserInfo($uwhere);

			$v["followcnt"] =  medoo()->count("zb_task_follow",[
				'AND'=>[
					'uniacid' => $this->conf->uniacid, 'uid' => $userid,
				],
			]);
			$followlist[$k]=$v;

		}
		ob_start();
		include $this->template('temp/mine/follower');
		$str = ob_get_contents();
		ob_end_clean();
	}
	if(in_array($_GPC['op'],array('userexe'))){
		$otherid = $_GPC['id'];

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$replyarr = medoo()->select("zb_task_reply",'*',[
			'AND'=>[
				'uniacid' => $this->conf->uniacid, 'uid' => empty($otherid) ?$userinfo["uid"]:$otherid ,
			],
			'LIMIT'=>[($pindex - 1) * $psize , $psize],
		]);
		$replylist = [];
		foreach($replyarr as $k => &$v){

			$v["time"] = date('Y-m-d h:i',$v['time']);

			$taskinfo = medoo()->get("zb_task_tasklist",'*',[
				"id"=>$v["taskid"],
			]);
			$v["taskinfo"] = $taskinfo;

			$replylist[$k]=$v;
		}
		ob_start();
		include $this->template('temp/mine/taskexe');
		$str = ob_get_contents();
		ob_end_clean();
	}

	if(in_array($_GPC['op'],array('myreport'))){
		$otherid = $_GPC['id'];
		$where = ["uid"=>empty($otherid)?$userinfo["uid"]:$otherid];
		$answerinfo = model_reply::getAllTaskAnswer($where,$_GPC['page']);
		$answerinfo = $answerinfo[0];
		$answerlist = [];
		foreach((array)$answerinfo as $k=> $v){
			$v["userstate"] = $userinfo["state"];
			
			if($userinfo['state'] == 2 ){
				$v["avatar"] = $userinfo['guydesc']['pic'];
				$v["nickname"] = $userinfo['guydesc']['manuname'];
			}
			
			
			$answer_extra = json_decode( $v['extra'],true);
			$v['inittime'] = date('Y-m-d h:i', $v["time"]);
			//回答所有的贴子
			$v["sectioncount"] = 0;
			$sectionitem = medoo()->select('zb_task_section','*',[
				"AND"=>[
					'uid'=>$v['uid'],
					'uniacid'=>$_W["uniacid"],
					'taskid'=>$v['taskid'],
				],
				'ORDER'=>' time desc ',
				'LIMIT'=>[0,1]
			]);
			if(count($sectionitem)>0){
				$sectionitem = $sectionitem[0];
				$difftime =  strtotime(date('Y-m-d',$sectionitem['time'])) -  strtotime(date('Y-m-d',$v['time']));
				$inday = intval($difftime/60/60/24);
				$inday<1 ? $inday = 1 :$inday += 1 ;
				$sectionitem["inday"] = $inday; //第几天
				$sectionitem["inittime"] = date('Y-m-d h:i', $sectionitem["time"]);//日期
				$sectionitem["images"] = json_decode($sectionitem["images"],true); //图片array
				$sectionitem["imgsum"] = count($sectionitem["images"]);//图片数

				//获取贴子点赞数
				$sectionitem['likesum'] = medoo()->count("zb_task_like",["sectionid"=>$sectionitem['id']]);
				$sectionitem['replysum']   = medoo()->count("zb_task_comment",["sectionid"=>$sectionitem['id']]);
				$v["sectionitem"] =  $sectionitem;
			}else{
				$difftime =  $v["overtime"] -  time();
				$endday = intval($difftime/60/60/24);
				$v["endday"] = $endday;
			}

			$t = Util::taskStat([
				"type"=>2,
				"objectid"=>$v["id"],
			]);
			$v["pv"] = $t["pv"];

			$v["lovenum"] = medoo()->count("zb_task_like",["AND"=>[
				'objectid'  => $v["id"],
				'sectionid' => 0,
				'type'      => 2,
				'pro'      => 1,]
			]);

			$answerlist[$k] = $v;
		}
		$answerinfo = $answerlist;
		ob_start();
		include $this->template('base/answer_item');
		$str = ob_get_contents();
		ob_end_clean();
	}
	//任务列表
	if(in_array($_GPC['op'],array('userpubed','new','high','urg','indexsch','love','guydetail'))){
		$city = trim(Util::getCookie('zb_task_city'));
		if($city == '') $city = '全国';
		$_GPC['status'] = isset($_GPC['status'])?$_GPC['status']:2;
		if($_GPC['status'] == 3) die; //不让显示审核中的任务

		if($_GPC['op'] == 'userpubed'){ //我发布的
			$otherid = $_GPC['id'];
			$wherearray = array('uid'=>empty($otherid)?$userinfo['uid']:$otherid);
			$wherearray['status'] = $_GPC['type'];
			if(empty($_GPC['type'])) unset($wherearray['status']);
			$order = 'a.id';

		}elseif(in_array($_GPC['op'],array('new','high'))){ //首页最新
			$wherearray['city'] = $city;
			if(!empty($_GPC["stage"]) && $_GPC["stage"] !=8){
				$wherearray['taskstage'] = util::$staticpubParam["taskstage"][(int)$_GPC["stage"]-1];
			}
			if($_GPC['status'] != '') $wherearray['status<'] = 2;
			if($_GPC['type'] != '') $wherearray['typeid'] = $_GPC['type'];
			$order = 'STATUS,createtime';

		}elseif($_GPC['op'] == 'indexsch'){ //搜索
			$wherearray = htmlspecialchars($_GPC['for']);
			$order = 'a.id';
			$taskinfo = model_task::getAllTask($wherearray,$order,10,$_GPC['page'],2,'app');
			
		}elseif($_GPC['op'] == 'urg'){ //加急
			$wherearray['city'] = $city;
			if($_GPC['status'] != '') $wherearray['status'] = $_GPC['status'];
			if($_GPC['type'] != '') $wherearray['typeid'] = $_GPC['type'];			
			$wherearray['urgmoney>'] = 0.01;
			$order = 'a.istop DESC,a.urgmoney';
			
		}elseif($_GPC['op'] == 'love'){ //收藏
			$otherid = $_GPC['id'];
			$taskinfo = model_love::getMyTaskLove(array('uid' => empty($otherid)?$userinfo['uid']:$otherid),$_GPC['page']);

		}elseif($_GPC['op'] == 'guydetail'){ //个人页面
			$wherearray = array('uid'=>$_GPC['id']);
			$order = 'a.id';		
			$taskinfo = model_task::getAllTask($wherearray,$order,5,$_GPC['page'],1,'app');
		}
		
		if($city == '全国' && $_GPC['op'] != 'indexsch') unset($wherearray['city']); //当是搜索时，$wherearray是一个字符串，不是数组。
		if($_GPC['status'] == 0 && $_GPC['op'] != 'indexsch') {
			unset($wherearray['status']);
			$wherearray['status@'] = ' (1,2) ';
		}

		//用户回答任务列表
		$doanswer = medoo()->select("zb_task_reply",'*',[
			'AND' =>[
				'uid'=>$userinfo['uid'],
				'uniacid'=>$_W["uniacid"],
			] ,
		]);
		$doanswer = array_column($doanswer,'uid','taskid');
		if(!in_array($_GPC['op'],array('indexsch','userexe','love','guydetail'))) $taskinfo = model_task::getAllTask($wherearray,$order,8,$_GPC['page'],1,'app');
		$taskinfo = $taskinfo[0];

		$tasklist = [];
		$currtime = time();

		$uidarr = array_column($taskinfo,'uid');
		$userarr = medoo()->select("zb_task_user","*",["uid"=>$uidarr]);
		$userarr = Util::keyBy($userarr,'uid');


		foreach((array)$taskinfo as $k => &$v){
//			$v['createtime'] = Util::formatTime($v['createtime']);

			$singuser = $userarr[$v["uid"]];
			$v["userstate"] = $singuser["state"];
			if($v["userstate"] ==2 && !empty($singuser['guydesc'])){
				$singuser['guydesc'] = json_decode($singuser['guydesc'],true);
				$v["avatar"] = $singuser['guydesc']['pic'];
				$v["nickname"] = $singuser['guydesc']['manuname'];
			}

			$v['createtime'] = date('Y-m-d h:i',$v['createtime']);
			if (strpos($v['pic'], 'http') === false) {
				$v['simg'] = $_W['attachurl'] . $v['pic'];
			}else{
				$v['simg'] = $v['pic'];
			}

			if($v["status"] ==1 && $v["lastnumber"] == 0 ){
				$v["status"] = 2;
			}

			if($_GPC["op"] != 'userpubed' && $v["status"] ==1 &&  !empty($doanswer[$v['id']]) ){
				$v["status"] = -1;
			}

			$task_extra = json_decode( $v['extra'],true);
			$v['extra'] = $task_extra;
				
			$v['title'] = strip_tags(htmlspecialchars_decode(htmlspecialchars_decode($v['title'])));
			$tasklist[$k] = $v;
		}
		ob_start();
		include $this->template('base/task_item');
		$str = ob_get_contents();
		ob_end_clean();
	}	

	if(in_array($_GPC['op'],array('checkreport'))){
		$checktype = $_GPC['op'];
		$where = ["state"=>1,"status"=>1];
		$answerinfo = model_reply::getAllTaskAnswer($where,$_GPC['page']);
		$answerinfo = $answerinfo[0];
		$answerlist = [];

		$uidarr = array_column($answerinfo,'uid');
		$userarr = medoo()->select("zb_task_user","*",["uid"=>$uidarr]);
		$userarr = Util::keyBy($userarr,'uid');

		foreach((array)$answerinfo as $k=> $v){

			$singuser = $userarr[$v["uid"]];
			$v["userstate"] = $singuser["state"];
			if($v["userstate"] ==2 && !empty($singuser['guydesc'])){
				$singuser['guydesc'] = json_decode($singuser['guydesc'],true);
				$v["avatar"] = $singuser['guydesc']['pic'];
				$v["nickname"] = $singuser['guydesc']['manuname'];
			}

			$answer_extra = json_decode( $v['extra'],true);
			$v['inittime'] = date('Y-m-d h:i', $v["time"]);
			//回答所有的贴子
			$v["sectioncount"] = 0;
			$sectionitem = medoo()->select('zb_task_section','*',[
				"AND"=>[
					'uid'=>$v['uid'],
					'uniacid'=>$_W["uniacid"],
					'taskid'=>$v['taskid'],
				],
				'ORDER'=>' time desc ',
				'LIMIT'=>[0,1]
			]);
			if(count($sectionitem)>0){
				$sectionitem = $sectionitem[0];
				$difftime =  strtotime(date('Y-m-d',$sectionitem['time'])) -  strtotime(date('Y-m-d',$v['time']));
				$inday = intval($difftime/60/60/24);
				$inday<1 ? $inday = 1 :$inday += 1 ;
				$sectionitem["inday"] = $inday; //第几天
				$sectionitem["inittime"] = date('Y-m-d', $sectionitem["time"]);//日期
				$sectionitem["images"] = json_decode($sectionitem["images"],true); //图片array
				$sectionitem["imgsum"] = count($sectionitem["images"]);//图片数

				//获取贴子点赞数
				$sectionitem['likesum'] = medoo()->count("zb_task_like",["sectionid"=>$sectionitem['id']]);
				$sectionitem['replysum']   = medoo()->count("zb_task_comment",["sectionid"=>$sectionitem['id']]);
				$v["sectionitem"] =  $sectionitem;
			}else{
				$difftime =  $v["overtime"] -  time();
				$endday = intval($difftime/60/60/24);
				$v["endday"] = $endday;
			}
			$t = Util::taskStat([
				"type"=>2,
				"objectid"=>$v["id"],
			]);
			$v["pv"] = $t["pv"];

			$v["lovenum"] = medoo()->count("zb_task_like",["AND"=>[
				'objectid'  => $v["id"],
				'sectionid' => 0,
				'type'      => 2,
				'pro'      => 1,]
			]);
			$answerlist[$k] = $v;
		}
		$answerinfo = $answerlist;
		ob_start();
		include $this->template('base/check_item');
		$str = ob_get_contents();
		ob_end_clean();

	}
	if(in_array($_GPC['op'],array('checktask'))){
		$checktype = $_GPC['op'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		//TODO 后期变化数据所有分页都需要修改
		$checkarr = medoo()->select("zb_task_tasklist",'*',[
			'AND'=>[
				'uniacid' => $this->conf->uniacid, 'status' => 3 ,
			],
			'LIMIT'=>[($pindex - 1) * $psize , $psize],
		]);
		$checklist = [];
		foreach($checkarr as $k => &$v){

			$v["time"] = date('Y-m-d h:i',$v['createtime']);
			$checklist[$k]=$v;
		}
		ob_start();
		include $this->template('base/check_item');
		$str = ob_get_contents();
		ob_end_clean();
	}
	if(in_array($_GPC['op'],array('checkmanu','checkmemb'))){
		$checktype = $_GPC['op'];
		$userstate = 4;
		if($_GPC['op'] == "checkmanu"){
			$userstate = 5;
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		//TODO 后期变化数据所有分页都需要修改
		$checkarr = medoo()->select("zb_task_user",'*',[
			'AND'=>[
				'uniacid' => $this->conf->uniacid, 'state' => $userstate ,
			],
			'LIMIT'=>[($pindex - 1) * $psize , $psize],
		]);
		$checklist = [];
		foreach($checkarr as $k => &$v){

			$v["time"] = date('Y-m-d h:i',$v['time']);

			$memberinfo = medoo()->get("mc_members",'*',[
				"uid"=>$v["uid"],
			]);
			$v["guydesc"] = json_decode($v["guydesc"],true);
			$v["nickname"] = $memberinfo["nickname"];
			$v["avatar"] = $memberinfo["avatar"];
			$checklist[$k]=$v;
		}
		ob_start();
		include $this->template('base/check_item');
		$str = ob_get_contents();
		ob_end_clean();
	}

	//资金记录、保证金记录、积分记录
	if(in_array($_GPC['op'],array('deposit','money','moneylog','depositlog','scorelog'))){
		if(empty($_GPC['id']) || $_GPC['id'] == "in"){
			$where['money>'] = 0;
		}else{
			$where['money<'] = 0;
		}
		$where['type'] = $_GPC['type'];
		if(empty($_GPC['type'])) unset($where['type']);
		$where['uid'] = $userinfo['uid'];

		$tablename = '';
		if($_GPC['op'] == 'deposit'){
			$tablename = 'zb_task_'.'depositlog';
		}else if($_GPC['op'] == 'money'){
			$tablename = 'zb_task_'.'moneylog';
		}else{
			$tablename = 'zb_task_'.$_GPC['op'];
		}

		$moneylog = model_moneylog::getAllMoneyLog($tablename,$where,$_GPC['page']);
		$moneylog = $moneylog[0];
		
		foreach($moneylog as $k=> &$v){
			$v['time'] = date('m-d',$v['time']);
			if($_GPC['op'] == 'money'){
				if($v['type'] == 1) $typestr = '发布任务消耗';
				if($v['type'] == 2) $typestr = '平台使用费';
				if($v['type'] == 3) $typestr = '提现扣除';
				if($v['type'] == 4) $typestr = '任务收益';
				if($v['type'] == 5) $typestr = '私包任务消耗';
				if($v['type'] == 6) $typestr = '私包取消退回';
				if($v['type'] == 7) $typestr = '私包任务收益';
				if($v['type'] == 8) $typestr = '任务结束退回';
				if($v['type'] == 9) $typestr = '加急任务消耗';
				if($v['type'] == 10) $typestr = '其他';	
				if($v['type'] == 11) $typestr = '退回提现';	
				if($v['type'] == 12) $typestr = '充值收入';	
				if($v['type'] == 13) $typestr = '任务不通过退回';
				if($v['type'] == 15) $typestr = '任务奖励差额退回';
			}elseif($_GPC['op'] == 'deposit'){
				if($v['type'] == 1) $typestr = '充值增加';
				if($v['type'] == 2) $typestr = '提取支出';
				if($v['type'] == 3) $typestr = '其他';				
				if($v['type'] == 4) $typestr = '退回提取';
				
			}elseif($_GPC['op'] == 'scorelog'){
				if($v['type'] == 1) $typestr = '拒绝回复而扣除';
				if($v['type'] == 2) $typestr = '回复被拒绝而扣除';
				if($v['type'] == 3) $typestr = '采纳回复而奖励';
				if($v['type'] == 4) $typestr = '回复被采纳而奖励';
				if($v['type'] == 5) $typestr = '取消私包任务而扣除';
				if($v['type'] == 6) $typestr = '其他';
			}
			$v["typestr"] = $typestr;
			if(empty($v['title'])){
				$title = $typestr;
			}else{
				$title = strip_tags(htmlspecialchars_decode(htmlspecialchars_decode($v['title'])));
			}

		}

		ob_start();
		include $this->template('base/record_item');
		$str = ob_get_contents();
		ob_end_clean();
	}
	
	if(in_array($_GPC['op'],array('question','userask','useranswer'))){
		for($i=0;$i<10;$i++){
			$str .= '';
		}
	}

	if(!empty($str) && !empty(trim($str))) $status = 'ok';
	if(empty($str) || empty(trim($str))) {
		if($_GPC['op'] == 'task'){
			$str = '<li class="no_data_notice no_data_notice_task"><p class="ti-face-sad"></p>没有更多内容</li>';
		}
		elseif(in_array($_GPC['op'],array('new','urg','high','pubguy','takeguy'))){
			$str = '<li class="no_data_notice"><p class="ti-face-sad"></p>没有更多任务</li>';
		}
		elseif(in_array($_GPC['op'],array('scorelog','userexe','userpubed',"myreport",'userfocus','follow',"fans",'love','moneylog','pvtpub','pvtaccept','indexsch','findsch'))){
			$str = '<li class="no_data_notice no_data_notice_task"><p class="ti-face-sad"></p>没有更多内容</li>';
		}
		elseif(in_array($_GPC['op'],array('checkreport','checkmanu','checkmemb','checktask'))){
			$str = '<li class="no_data_notice no_data_notice_task"><p class="ti-face-sad"></p>没有待审核内容</li>';
		}
		elseif(in_array($_GPC['op'],array('deposit','money'))){
			$str = '<li class="no_data_notice no_data_notice_task"><p class="ti-face-sad"></p>没有提现与充值记录</li>';
		}
	}
	$data = array('status'=>$status,'data'=>$str);
	echo json_encode($data);	

?>
