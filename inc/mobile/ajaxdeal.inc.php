<?php

global $_GPC, $_W;
$this->userinfo = model_user::initUserInfo(); //用户信息
$_GPC = Util::trimWithArray($_GPC);

//方法映射
$opers = [
    'location','robtask', 'publish', 'publishsection', 'tovertify', 'replaytask', 'tasksetting', 'dealreply', 'accounttask', 'love', 'updatauser',
    'setting', 'refusegeivetask', 'completetask', 'canceltask', 'confirmtask', 'confirmrefuse', 'acceptrefuse','doendreport',
    'omplainboss', 'workertaketask', 'workerrefusetask', 'getmoney', 'drwdeposit', 'verifytask', 'uploadimages', 'dolike','dolikereport','likelist',
];

//指定的操作不存在则退出
$action = $this->request->op;
in_array($action, $opers) ? $action($this) : exit('指定的操作不存在！');

function location($taskObject)
{
    $url     = 'http://api.map.baidu.com/geocoder/v2/?ak=' . $taskObject->module['config']['locationak'] . '&location=' . $taskObject->request->latitude . ',' . $taskObject->request->longitude . '&output=json&pois=1';
    $opt     = ['http' => ['header' => "Referer: " . $taskObject->conf->siteroot,],];
    $context = stream_context_create($opt);
    $result  = file_get_contents($url, false, $context);
    $taskObject->renderJs($result);
}

function robtask($taskObject){
    $taskid  = $taskObject->request->taskid;
    $res = medoo()->update("zb_task_tasklist",['lastnumber[-]'=>1],[
        'AND'=>[
            'id'=>$taskid,
            'lastnumber[>]'=>0,
        ]
    ]);

    if(empty($res)){
        $taskObject->renderJs(["errcode" => 2, "errmsg" => "任务已抢完",]);
    }
    $taskinfo        = medoo()->get("zb_task_tasklist", '*', ['id' => $taskid]);
    $task_extra = json_decode( $taskinfo['extra'],true);
    $answerRes     = pdo_insert('zb_task_reply', [
        'uniacid'  =>  $taskObject->conf->uniacid,
        'openid'   => $taskObject->userinfo['openid'],
        'uid'      =>  $taskObject->userinfo['uid'],
        'time'     => time(),
        'taskid'   => $taskid,
        'status'   => 1,
        'income'   => 0,
        'replynum' => 0,
        'overtime' => ($task_extra["taskcycle"]*86400+time()),
        'grade'    => 0,
        'state'    => 0,
    ]);
    $taskObject->renderJs(["errcode" => 0, "errmsg" => "success",]);
}

function doendreport($taskObject){
    $replyid  = $taskObject->request->rid;
    $grade = $taskObject->request->grade;
    medoo()->update("zb_task_reply",["state"=>1,"grade"=>$grade],[
        "id"=>$replyid,
    ]);
    $taskObject->renderJs(["errcode" => 0, "errmsg" => "success",]);
}

function publish($taskObject)
{
    $result = model_task::publishTask('app', (array)$taskObject->request, $taskObject);
    $taskObject->renderJs($result);
}

function publishsection($taskObject)
{
    $taskid  = $taskObject->request->taskid;
    $sims    = $taskObject->request->imgs_extra;
    $content = $taskObject->request->content;
    $address = $taskObject->request->address;

    if(empty($content) && empty($sims)) {
        $taskObject->renderJs(['status' => 1, 'errmsg' => '至少说点或一张图！']);
    }
    if($taskObject->userinfo['creditscore'] <= 0) {
        $taskObject->renderJs(['status' => 7, 'errmsg' => '您的信誉分数小于0，不能回答任务。']);
    }

    $taskinfo        = medoo()->get("zb_task_tasklist", '*', ['id' => $taskid]);
    $data["taskid"]  = $taskid;
    $data["images"]  = json_encode($sims);
    $data["content"] = $content;
    $data["status"]  = 0;
    $data['uniacid'] = $taskObject->conf->uniacid;
    $data['openid']  = $taskObject->userinfo['openid'];
    $data['uid']     = $taskObject->userinfo['uid'];
    $data['time']    = time();
    $data["address"] = $address;
    $replystate      = $taskObject->request->state;
    $replygrade      = $taskObject->request->grade;

    //获取是否有回答过
    $reply = medoo()->get('zb_task_reply', '*', [
        'AND' => [
            'taskid'  => $taskid,
            'uniacid' => $taskObject->conf->uniacid,
            'uid'     => $taskObject->userinfo['uid'],
        ],
    ]);
    //扩展属性
    if(empty($reply)) {

        $extra             = [];
        $staticanswerParam = util::$staticanswerParam;
        foreach($staticanswerParam as $index => $row) {
            $paramname          = $row['paramname'];
            $paramtitle         = $row['paramtitle'];
            $extra[$paramtitle] = $taskObject->request->{$paramname};
        }
        $extra         = json_encode($extra);
        $data["extra"] = $extra;
        $task_extra = json_decode( $taskinfo['extra'],true);
        $overtime = $task_extra["taskcycle"]*86400+$data['time'];
        $answerRes     = pdo_insert('zb_task_reply', [
            'uniacid'  => $data['uniacid'],
            'openid'   => $data['openid'],
            'uid'      => $data['uid'],
            'time'     => $data['time'],
            'taskid'   => $data['taskid'],
            'address'  => $data["address"],
            'extra'    => $extra,
            'status'   => 1,
            'income'   => 0,
            'replynum' => 1,
            'overtime' => $replystate == 1 ? $data['time'] :$overtime,
            'grade'    => !empty($replygrade) ? $replygrade : 0,
            'state'    => $replystate == 1 ? $replystate : 0,
        ]);
        $insertid = pdo_insertid();
        $data['replyid'] = $insertid;
    }else if($reply["replynum"] == 0){
        $extra             = [];
        $staticanswerParam = util::$staticanswerParam;
        foreach($staticanswerParam as $index => $row) {
            $paramname          = $row['paramname'];
            $paramtitle         = $row['paramtitle'];
            $extra[$paramtitle] = $taskObject->request->{$paramname};
        }
        $taskinfo['extra'] = json_decode( $taskinfo['extra'],true);
        $answerexplain = '我种植'.$taskinfo["extra"]["taskvariety"].',树龄'.$extra['树龄'].'年'.',时期'.$taskinfo["extra"]["taskstage"].',使用倍数'.$taskinfo["extra"]["taskmultiple"]."(".(int)(15000/$taskinfo["extra"]["taskmultiple"])."ml)".',使用方式'.$taskinfo["extra"]["taskway"]."。";

        $extra         = json_encode($extra);
        $data['replyid'] = $reply["id"];
        $data["extra"]  = $extra;
        $data['income'] = $taskinfo["money"] + $taskinfo["urgmoney"];
        $updatedata     = [];
        if($replystate == 1) {
            $updatedata["state"]    = 1;
            $updatedata["overtime"] = $data['time'];
            $updatedata["grade"]    = $replygrade;
        }
        $updatedata["replynum"] = $reply["replynum"] + 1;
        $updatedata["address"] = $data["address"];
        $updatedata["extra"] = $extra;
        $updatedata["replynum"] = $reply["replynum"] + 1;
        $updatedata['title'] = $answerexplain;
        medoo()->update('zb_task_reply', $updatedata, [
            'AND' => [
                'taskid'  => $taskid,
                'uniacid' => $taskObject->conf->uniacid,
                'uid'     => $taskObject->userinfo['uid'],
            ],
        ]);
    }
    else {
        $extra          = $reply['extra'];
        $data['replyid'] = $reply["id"];
        $data["extra"]  = $extra;
        $data['income'] = $taskinfo["money"] + $taskinfo["urgmoney"];
        $updatedata     = [];
        if($replystate == 1) {
            $updatedata["state"]    = 1;
            $updatedata["overtime"] = $data['time'];
            $updatedata["grade"]    = $replygrade;
        }
        $updatedata["replynum"] = $reply["replynum"] + 1;
        medoo()->update('zb_task_reply', $updatedata, [
            'AND' => [
                'taskid'  => $taskid,
                'uniacid' => $taskObject->conf->uniacid,
                'uid'     => $taskObject->userinfo['uid'],
            ],
        ]);
    }
    $res = pdo_insert('zb_task_section', $data);
    if($res) {
        model_user::deleteUserCache($taskObject->userinfo['openid']);//删除用户缓存
        //给管理员发通知
        if(!empty($moduel->module['config']['adminopenid'])) {
            Message::cmessage($moduel->module['config']['adminopenid'], $moduel, $data['title'], 'pubtask', $insertid);//发通知
        }
        $taskObject->renderJs(['status' => 5, 'id' => $insertid]);
    }
    $taskObject->renderJs(['status' => 6]);
}

function tovertify($taskObject)
{
    if(!empty($_SESSION['mobilevertify']) && $_SESSION['mobilevertifytime'] > time()) {
        die('0');
    }
    require_once zb_task . 'lib/alidayu/Autoloader.php';
    $code         = rand(1000, 9999);
    $c            = new TopClient;
    $c->appkey    = $taskObject->module['config']['sendkey'];
    $c->secretKey = $taskObject->module['config']['sendsecret'];
    $req          = new AlibabaAliqinFcSmsNumSendRequest;
    $req->setExtend("123456");
    $req->setSmsType("normal");
    $req->setSmsFreeSignName($taskObject->module['config']['sendsignature']);
    $req->setSmsParam('{"code":"' . $code . '","product":"' . $taskObject->module['config']['sendproduct'] . '","customer":"' . $taskObject->userinfo['nickname'] . '"}');
    $req->setRecNum($taskObject->request->mobile);
    $req->setSmsTemplateCode($taskObject->module['config']['sendtemplate']);
    $resp = $c->execute($req);

    $res = json_decode(json_encode($resp), true);
    if($res['result']['success']) {
        $_SESSION['mobilevertify']     = md5($code);
        $_SESSION['mobilevertifytime'] = time() + 300;
        $_SESSION['mobilenumber']      = $taskObject->request->mobile;
        die('2');
    } else {
        die('3');
    }
}

function replaytask($taskObject)
{
    $data['taskid']  = intval($taskObject->request->taskid);
    $data['images']  = iserializer($taskObject->request->imgaes);
    $data['content'] = htmlspecialchars($taskObject->request->content);

    $taskinfo = model_task::getSingleTask($data['taskid']);

    if($taskinfo['lastnumber'] == 0) die('1'); //已经没有票数了
    if($taskinfo['uid'] == $taskObject->userinfo['uid']) die('2'); //自己的
    if($taskObject->userinfo['creditscore'] <= 0) die('4'); //信誉分数太低
    if($taskinfo['status'] != 1 || $taskinfo['overtime'] <= time()) die('6'); //已经结束了

    $replynumber = Util::countData('zb_task_reply', ['uid' => $taskObject->userinfo['uid'], 'taskid' => $taskinfo['id']]);
    if($replynumber >= $taskinfo['maxreply']) die('5'); //限制票数

    $data['uniacid'] = $taskObject->conf->uniacid;
    $data['uid']     = $taskObject->userinfo['uid'];
    $data['openid']  = $taskObject->userinfo['openid'];
    $data['time']    = time();
    $data['status']  = 1;
    $data['income']  = $taskinfo['money'] + $taskinfo['urgmoney'];

    $res = pdo_insert('zb_task_reply', $data);
    if($res) {
        //改变任务的数据
        $urgnumber = 0;
        if($taskinfo['urgnumber'] > 0 && $taskinfo['urgmoney'] > 0) $urgnumber = -1;
        model_task::updateTaskTable(['lastnumber' => -1, 'urgnumber' => $urgnumber], $data['taskid']);
        //发通知给作者,管理员发布的不发通知
        if($taskinfo['uid'] > 0) Message::bmessage($taskinfo['openid'], $taskObject, $taskObject->userinfo['nickname'], $data['content'], $taskinfo['id']);
        die('3');
    }
}

function tasksetting($taskObject)
{
    $id               = intval($taskObject->request->taskid);
    $isurg            = intval($taskObject->request->isurg);
    $data['urgmoney'] = $taskObject->request->urgmoney;
    $data['isshow']   = intval($taskObject->request->ishide);

    $taskinfo = model_task::getSingleTask($id);

    if($taskinfo['uid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 1 || $taskinfo['lastnumber'] <= 0) die;

    if($isurg == 1) { //加急
        if($data['urgmoney'] < $taskObject->module['config']['urgleastmoney'] || $data['urgmoney'] <= 0) die; //小于限定的值
        if($taskinfo['urgmoney'] > 0) die; //已经加急处理了
        $money  = $taskinfo['lastnumber'] * $data['urgmoney'];
        $server = $taskObject->module['config']['servermoney'] * $money / 100;
        $total  = $money + $server; //合计

        $taskObject->userinfo = model_user::getSingleUserInfo(['uid' => $taskObject->userinfo['uid']]);

        if($taskObject->userinfo['credit2'] < $total || $total <= 0) die('1'); //钱不够
        $res = model_user::updateUserCredit2($taskObject->userinfo['uid'], -$money); //扣钱
        if($res) model_moneylog::insertMoneyLog($taskObject->userinfo['uid'], $taskObject->userinfo['openid'], $taskinfo['id'], $taskinfo['title'], -$money, 9); //资金记录
        $res = model_user::updateUserCredit2($taskObject->userinfo['uid'], -$server); //扣钱
        if($res) model_moneylog::insertMoneyLog($taskObject->userinfo['uid'], $taskObject->userinfo['openid'], $taskinfo['id'], $taskinfo['title'], -$server, 2); //资金记录

        if($res) {
            $data['urgnumber']  = $taskinfo['lastnumber'];
            $data['addurgtime'] = time();
            $res                = Util::updateSingleData('zb_task_tasklist', $data, ['id' => $taskinfo['id']]); //更新任务
            model_user::deleteUserCache($taskObject->userinfo['openid']);//删除用户缓存
        }
        die('2');
    }
    if($taskinfo['isshow'] == $data['isshow'] && $isurg == 0) die('2'); //只改变是否隐藏，如果已经是需改变的状态就没必要改了。

    $res = Util::updateSingleData('zb_task_tasklist', ['isshow' => $data['isshow']], ['id' => $taskinfo['id']]); //更新任务
    if($res) die('2');
    die;
}

function dealreply($taskObject)
{
    global $_GPC;
    $res = model_task::acceptRplyAndRefuseReply($_GPC, 'app', $taskObject->userinfo['uid'], $taskObject);
    $taskObject->renderJs($res);
}

function accounttask($taskObject)
{
    $data['taskid'] = intval($taskObject->request->taskid);
    $taskinfo       = medoo()->get('zb_task_tasklist', '*', ['id' => $data['taskid']]);
    if(empty($taskinfo) || $taskinfo['uid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 1) {
        $taskObject->renderJs(['status' => 3, 'errmsg' => '任务不存在或已结束']);
    }
    $res = model_task::accountTaskInAjaxdealAndCrontab($taskinfo, $taskObject);
    $taskObject->renderJs(['status' => 1, 'errmsg' => 'success']);
}

function love($taskObject)
{
    $data['taskid'] = intval($taskObject->request->taskid);
    $loveinfo       = model_love::getSingleLove($data['taskid'], $taskObject->userinfo['uid']);
    if(empty($loveinfo)) {
        $data['uniacid'] = $taskObject->conf->uniacid;
        $data['openid']  = $taskObject->conf->openid;
        $data['uid']     = $taskObject->userinfo['uid'];
        $res             = pdo_insert('zb_task_love', $data);
    } else {
        $res = pdo_delete('zb_task_love', ['id' => $loveinfo['id'], 'uniacid' => $taskObject->conf->uniacid], 'AND');
    }
    if($res) die('2');
}

function updatauser($taskObject)
{
    if(!empty($_COOKIE['updataed'])) {
        die('1');
    }
    setcookie('updataed', '1', time() + 120, '/');
    model_user::deleteUserCache($taskObject->userinfo['openid']);
    die('2');

}

function setting($taskObject)
{
    $data['mobile']  = $taskObject->request->mobile;
    $data['qrcode']  = $taskObject->request->images;
    $data['guytype'] = $taskObject->request->guytype;
    $data['guysort'] = $taskObject->request->guysort;
    $data['guydesc'] = $taskObject->request->guydesc;
    $data['city']    = $taskObject->request->city;
    $contacttype1    = $taskObject->request->contacttype1;
    $contacttype2    = $taskObject->request->contacttype2;

    if($taskObject->userinfo['deposit'] < $taskObject->module['config']['deposit']) die('3');

    if($contacttype1 == '' && $contacttype2 == '') $data['contacttype'] = 0;
    if($contacttype1 == '1' && $contacttype2 == '2') $data['contacttype'] = 3;
    if($contacttype1 == '1' && $contacttype2 == '') $data['contacttype'] = 1;
    if($contacttype1 == '' && $contacttype2 == '2') $data['contacttype'] = 2;

    //验证手机号码
    if($taskObject->module['config']['issend'] == 1 && (md5($taskObject->request->mobilecode) != $_SESSION['mobilevertify'] || $data['mobile'] != $_SESSION['mobilenumber'])) die('4');
    $res = pdo_update('zb_task_user', $data, ['uniacid' => $taskObject->conf->uniacid, 'uid' => $taskObject->userinfo['uid']]);
    model_user::deleteUserCache($taskObject->userinfo['openid']);//删除缓存
    if($res) die('1');
    die('2');
}

function refusegeivetask($taskObject)
{
    $taskid   = intval($taskObject->request->taskid);
    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['type'] != 1 || $taskinfo['bossuid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 0) die;
    //改变任务状态
    $res = model_privatetask::updateSingleTask(['status' => 1, 'accepttime' => time()], ['id' => $taskinfo['id']]);

    //发通知
    Message::cmessage($taskinfo['workeropenid'], $taskObject, $taskinfo['tasktitle'], 'refusegeivetask', $taskinfo['id']);

    if($res) die('1');
    die('2');

}

function completetask($taskObject)
{
    $taskid            = intval($taskObject->request->taskid);
    $content['title']  = htmlspecialchars($taskObject->request->completecontent);
    $content['images'] = $taskObject->request->images;
    $content           = iserializer($content);

    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['workeruid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 2) die;
    //改变任务状态
    $overtime3 = $taskObject->module['config']['privatedealtime'] * 3600 + time();
    $res       = model_privatetask::updateSingleTask(['status' => 3, 'workerdealtime' => time(), 'overtime3' => $overtime3, 'completecontent' => $content], ['id' => $taskinfo['id']]);

    //发通知
    Message::cmessage($taskinfo['bossopenid'], $taskObject, $taskinfo['tasktitle'], 'completetask', $taskinfo['id']);

    if($res) die('1');
    die('2');

}

function canceltask($taskObject)
{
    $taskid   = intval($taskObject->request->taskid);
    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['workeruid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 2) die;

    $res = model_privatetask::cancelTaskFuncInAjaxdealAndCrontab($taskinfo, 4, $taskObject); //处理任务 //此方法里已发通知

    if($res) die('1');
    die('2');
}

function confirmtask($taskObject)
{
    $taskid   = intval($taskObject->request->taskid);
    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['bossuid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 3) die;

    $res = model_privatetask::completeTaskInajaxdealAndCrontab($taskinfo, 6, $taskObject); // 此方法中已加入通知

    if($res) die('1');
    die('2');
}

function confirmrefuse($taskObject)
{
    $taskid       = intval($taskObject->request->taskid);
    $refusereason = htmlspecialchars($taskObject->request->refusereason);

    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['bossuid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 3 || empty($refusereason)) die;

    //改变任务状态
    $overtime7 = $taskObject->module['config']['privatedealtime'] * 3600 + time();
    $res       = model_privatetask::updateSingleTask(['status' => 7, 'bossdealtime' => time(), 'overtime7' => $overtime7, 'refusereason' => $refusereason], ['id' => $taskinfo['id']]);

    //发通知
    Message::cmessage($taskinfo['workeropenid'], $taskObject, $taskinfo['tasktitle'], 'confirmrefuse', $taskinfo['id']);

    if($res) die('1');
    die('2');

}

function acceptrefuse($taskObject)
{
    $taskid   = intval($taskObject->request->taskid);
    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['workeruid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 7) die;
    $res = model_privatetask::acceptRefuseRusultInAjaxAndCronb($taskinfo, 8, $taskObject); //此方法里已发通知
    if($res) die('1');
    die('2');
}

function omplainboss($taskObject)
{
    $taskid       = intval($taskObject->request->taskid);
    $refusereason = htmlspecialchars($taskObject->request->refusereason);

    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['workeruid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 7 || empty($refusereason)) die;

    //改变任务状态
    $res = model_privatetask::updateSingleTask(['status' => 9, 'complaintime' => time(), 'complainreason' => $refusereason], ['id' => $taskinfo['id']]);

    //发通知
    Message::cmessage($taskinfo['bossopenid'], $taskObject, $taskinfo['tasktitle'], 'omplainboss', $taskinfo['id']);

    if($res) die('1');
    die('2');
}

function workertaketask($taskObject)
{
    $taskid = intval($taskObject->request->taskid);

    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['workeruid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 0) die;

    $overtime2 = $taskinfo['limittime'] * 3600 + time();
    //改变任务状态
    $res = model_privatetask::updateSingleTask(['status' => 2, 'accepttime' => time(), 'overtime2' => $overtime2], ['id' => $taskinfo['id']]);

    //发通知
    Message::cmessage($taskinfo['bossopenid'], $taskObject, $taskinfo['tasktitle'], 'workertaketask', $taskinfo['id']);

    if($res) die('1');
    die('2');

}

function workerrefusetask($taskObject)
{
    $taskid = intval($taskObject->request->taskid);

    $taskinfo = model_privatetask::getSinglePrivateTaskNoInner(['id' => $taskid]);
    if($taskinfo['workeruid'] != $taskObject->userinfo['uid'] || $taskinfo['status'] != 0) die;

    //改变任务状态
    $res = model_privatetask::updateSingleTask(['status' => 1, 'accepttime' => time()], ['id' => $taskinfo['id']]);

    //退回资金
    if($res) $res = model_privatetask::backMoneyToBossInPrivateTask($taskinfo);        //这里已删除缓存

    //发通知
    Message::cmessage($taskinfo['bossopenid'], $taskObject, $taskinfo['tasktitle'], 'workerrefusetask', $taskinfo['id']);

    if($res) die('1');
    die('2');
}

function getmoney($taskObject)
{
    $type  = $taskObject->request->type;
    $money = $taskObject->request->money;
//		if(($type != 'all' && $type != 'other') || ($type == 'other' && $taskObject->request->money <= 0)) die('0');

    $drwedinfo = Util::getSingleData('zb_task_drwmoney', ['uid' => $taskObject->userinfo['uid'], 'status' => 0]);
    if(!empty($drwedinfo)) die('5');

    $taskObject->userinfo = model_user::getSingleUserInfo(['uid' => $taskObject->userinfo['uid']]);
    if($taskObject->userinfo['credit2'] < $money) die('1'); //钱不够
    if($money < $taskObject->module['config']['leastdraw'] || $money < 1) die('2'); //小于最小的限值

    if($type == 'all') $data['money'] = $taskObject->userinfo['credit2'];
    if($type == 'other') $data['money'] = $money;

    $res = model_drwmoney::insertDrwLog($taskObject->userinfo, $money, $taskObject); //已发通知

    if($res) die('3');
    die('4');
}

function drwdeposit($taskObject)
{
    $fee            = $taskObject->request->drwdeposit;
    $taskObject->userinfo = model_user::getSingleUserInfo(['openid' => $taskObject->conf->openid]); //用户信息

    if($taskObject->userinfo['deposit'] < ($fee + $taskObject->module['config']['leastkeepdeposit']) || $fee <= 0) die('1');

    $drwinfo = Util::getSingleData('zb_task_drwdeposit', ['status' => 0, 'uid' => $taskObject->userinfo['uid']]);//查询是否已有提取的
    if(!empty($drwinfo)) die('4');
    if(model_privatetask::issetPrivatetasking($taskObject->userinfo['uid'], '', 'drwdeposit') || model_task::issetTasking($taskObject->userinfo['uid'])) die('5'); //检查是否有没完成的任务


    $res = model_drwdeposit::insertDrwLog($taskObject->userinfo, $fee, $taskObject); //已插入记录，已更新缓存 ,这里故意使用正数金额让提取表里的金额为正数

    if($res) die('2');
    die('3');
}

function verifytask($taskObject)
{
    $id   = intval($taskObject->request->taskid);
    $type = intval($taskObject->request->type);
    if($taskObject->userinfo['openid'] != $taskObject->module['config']['adminopenid'] || empty($taskObject->module['config']['adminopenid'])) die;
    $taskinfo = model_task::getSingleTask($id);
    if($taskinfo['status'] != 3) die;

    if($type == 1) { //审核不通过
        $res = model_task::verifyTaskWithNoPass($taskObject, $taskinfo);
    } elseif($type == 2) { //审核通过
        $res = model_task::verifyTaskWithPass($taskObject, $id);
    }

    if($res) die('1');
    die('2');
}

function uploadimages($taskObject)
{
    load()->model('account');
    load()->func('communication');

    $access_token = WeAccount::token();
    $url          = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . $access_token . '&media_id=' . $taskObject->request->serverId;
    $resp         = ihttp_get($url);
    $res          = Util::uploadImageInWeixin($resp);
    echo $res;
    die;
}

function dolike($taskObject)
{
    $system = $taskObject->module['config'];
    if(empty($taskObject->userinfo['openid'])) {
        $taskObject->renderJs(["errcode" => 1, "errmsg" => 'un auth']);
    }
    $sectionid   = $taskObject->request->sectionid;
    $operatetype = intval($taskObject->request->operatetype);
    $section = medoo()->get("zb_task_section",'*',["id"=>$sectionid]);
    if($operatetype == 1) {
        //如果存不作处理
        $likeobj = medoo()->get("zb_task_like", '*', [
            'AND' => [
                'objectid' => $section['replyid'],
                'sectionid' => $sectionid,
                'uid'       => $taskObject->userinfo['uid'],
                'uniacid'   => $taskObject->conf->uniacid,
                'type'=>2,
            ],
        ]);
        if(empty($likeobj)) {
            $data = ['uniacid' => $taskObject->conf->uniacid,
                        'sectionid' => $sectionid,
                        'openid' => $taskObject->userinfo['openid'],
                        'uid' => $taskObject->userinfo['uid'],
                        'headimgurl' => $taskObject->userinfo['avatar'],
                        'nickname' => $taskObject->userinfo['nickname'],
                        'addtime' => date("Y-m-d H:i:s"),
                        'objectid'=>$section['replyid'],
                        'type'=>2,
                        'pro'=>1,
                    ];
            pdo_insert("zb_task_like", $data);
            $id = pdo_insertid();

        } else  if($likeobj["pro"]==1){
            $result = ["errcode" => 1, "errmsg" => "已点过赞"];
        }else{
            medoo()->update("zb_task_like",["pro"=>1], ['AND'=>[
                'objectid' => $section['replyid'],
                'sectionid' => $sectionid,
                'uid'       => $taskObject->userinfo['uid'],
                'uniacid'   => $taskObject->conf->uniacid,
                'type'=>2,]
            ]);
        }

    } else {
        medoo()->update("zb_task_like",["pro"=>0], ['AND'=>[
            'objectid' => $section['replyid'],
            'sectionid' => $sectionid,
            'uid'       => $taskObject->userinfo['uid'],
            'uniacid'   => $taskObject->conf->uniacid,
            'type'=>2,
            'pro'=>1,]
        ]);
    }
    $taskObject->renderJs(["errcode" => 0, "errmsg" => "success"]);
}

function dolikereport($taskObject)
{
    $replyid = $taskObject->request->replyid;
    $operatetype = $taskObject->request->operatetype;

    if(empty($replyid)){
        $this->renderJs(["errcode" => 1, "errmsg" => '未传入审核者ID']);
    }

    //判断是否存在记录
    $re = medoo()->get("zb_task_like",'*',[
           'AND'=>[
               'objectid'  => $replyid,
               'sectionid' => 0,
               'type'      => 2,
               'uid'   => $taskObject->userinfo['uid'],
           ]
        ]);
    if(empty($re)){
        $a = Util::taskStatAdd([
            'objectid'  => $replyid,
            'sectionid' => 0,
            'type'      => 2,
            'like'      => $operatetype,
        ]);
    }else{
        medoo()->update("zb_task_like",["pro"=>$operatetype],[
            'AND'=>[
                'objectid'  => $replyid,
                'sectionid' => 0,
                'type'      => 2,
                'uid'   => $taskObject->userinfo['uid'],
            ],
        ]);
    }


    $lovenum = medoo()->count("zb_task_like",["AND"=>[
        'objectid'  => $replyid,
        'sectionid' => 0,
        'type'      => 2,
        'pro'      => 1,]
    ]);
    $opposenum = medoo()->count("zb_task_like",["AND"=>[
        'objectid'  => $replyid,
        'sectionid' => 0,
        'type'      => 2,
        'pro'      => 2,]
    ]);
    $taskObject->renderJs(["errcode" => 0, "errmsg" => "success",'love'=>$lovenum ,"oppose"=>$opposenum]);
}

/**
 * 点赞列表
 */
 function likelist($taskObject)
{
    $sectionid = $taskObject->request->sectionid;
    $sql       = "SELECT DISTINCT uid,headimgurl,openid FROM " . tablename("zb_task_like") . "  WHERE uniacid = '{$taskObject->conf->uniacid }' and sectionid = '{$sectionid}' and type=2 and pro =1  ORDER BY id desc";
    $list      = pdo_fetchall($sql);
    $likesum = count($list);
    $likelist = [];
    foreach ($list as $index => $row) {
        if ($index == 6) {
            break;
        }
        $likelist[$index] = $row;
    }

    $result = ["errcode" => 0, "errmsg" => "success", 'likesum' => $likesum, "likelist" => $likelist];
    $taskObject->renderJs($result);
}

