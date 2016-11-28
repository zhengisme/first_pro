<?php
global $_W,$_GPC;
$userinfo = model_user::initUserInfo(); //用户信息
$_GPC["op"] = "taskreport";
$initParams = array(
    'title' => '报告详情',
    'insertelem' =>'.td-detail'
);
$tobe = $_GPC['tobe'];
//获取reply
$replysql = "SELECT a.*,b.nickname,b.credit2,b.avatar FROM " . tablename('zb_task_reply') . " AS a INNER JOIN " . tablename('mc_members') . " AS b ON a.uid = b.uid WHERE a.`uniacid` ='{$_W['uniacid']}'and a.id = {$_GPC['id']} " ;
$reply = pdo_fetch($replysql);

if(empty($reply)) message("报告不存在");
$reply['inittime'] = date('Y-m-d h:i', $reply["time"]);

if(($reply['overtime']-time())<=0){
    $reply['state'] =1;
    medoo()->update("zb_task_reply",["state"=>1],["id"=>$reply["id"]]);
}

$taskinfo = medoo()->get("zb_task_tasklist",'*',["id"=>$reply["taskid"]]);
$taskinfo["extra"] = json_decode( $taskinfo['extra'],true);

$answer_extra = json_decode( $reply['extra'],true);
//说明
$answerexplain = '我种植'.$taskinfo["extra"]["taskvariety"].',树龄'.$answer_extra['树龄'].'年'.',时期'.$taskinfo["extra"]["taskstage"].',使用倍数'.$taskinfo["extra"]["taskmultiple"]."(".(int)(15000/$taskinfo["extra"]["taskmultiple"])."ml)".',使用方式'.$taskinfo["extra"]["taskway"]."。";


$a = Util::taskStatAdd([
    'objectid'  => $reply['id'],
    'sectionid' => 0,
    'type'      => 2,
    'like'      => 0,
]);

$reply['sectioncnt'] = medoo()->count("zb_task_section",[
    "AND"=>[
      'uid'=>  $reply["uid"],
        'taskid'=>$reply["taskid"],
    ],
]);
$reply["commentcnt"] = 0;
$reply["likecnt"] = 0;


//是否关注
if($reply["uid"] != $userinfo['uid']){
    $filter   = [
        'AND' => [
            'uniacid' => $this->conf->uniacid, 'fid' => $reply["uid"], 'uid' => $userinfo['uid'],
        ],
    ];
    $followed = medoo()->has("zb_task_follow", $filter);
}



$wx_share  = [
    'stitle'    => '试验报告',
    'sdesc'     => $answerexplain,
    'slink'     => $_W['siteroot'] . 'app/' . $this->createMobileUrl('taskreport',['id' => $reply["id"],'op'=>'task']),
    'simgUrl'   => $_W['attachurl'] . $taskinfo['pic'],
    'hideMenu'  => intval(0),
];

$longurl = 'http://api.t.sina.com.cn/short_url/shorten.json?source=1681459862&url_long='.urlencode($wx_share['slink']);
try{
    $response = file_get_contents($longurl);
}catch (Exception $e)
{
    //display custom message
}
if(is_error($response)) {
    $result = error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
    $short_url = $wx_share['slink'];
}else{
    $shortRes = @json_decode($response);
    $shortRes = $shortRes[0];
    if(empty($shortRes)){
        $short_url = $wx_share['slink'];
    }else{
        $short_url = $shortRes->url_short;
    }
}
if(empty($short_url)){
    $short_url = $wx_share['slink'];
}

$creator = medoo()->get("zb_task_user",'*',['uid'=>$reply["uid"]]);

if($creator["state"] ==2 && !empty($creator['guydesc'])){
    $creator['guydesc'] = json_decode($creator['guydesc'],true);
    $reply["avatar"] = $creator['guydesc']['pic'];
    $reply["nickname"] = $creator['guydesc']['manuname'];
}


$followcnt = medoo()->count("zb_task_follow",["fid"=>$reply["uid"]]);

$t = Util::taskStat([
    "type"=>2,
    "objectid"=>$reply["id"],
]);
$reply["pv"] = $t["pv"];

$lovenum = medoo()->count("zb_task_like",["AND"=>[
    'objectid'  => $reply["id"],
    'sectionid' => 0,
    'type'      => 2,
    'pro'      => 1,]
]);
$opposenum = medoo()->count("zb_task_like",["AND"=>[
    'objectid'  => $reply["id"],
    'sectionid' => 0,
    'type'      => 2,
    'pro'      => 2,]
]);

include $this->template('taskreport');
?>