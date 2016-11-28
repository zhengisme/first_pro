<?php
global $_W, $_GPC;
$userinfo = model_user::initUserInfo(); //用户信息


if(!empty($_GPC['id']) && $userinfo["uid"] != $_GPC['id'] ){
    $otherid = $_GPC['id'];
    $where = array("uid"=>$_GPC['id']);
    $userinfo = model_user::getSingleUserInfo($where);
}
$insertelem = '.m-mine-list';
$_GPC['op'] = empty($_GPC['op']) ? 'follow' : $_GPC['op'];
switch ($_GPC['op']) {
    case 'follow' :
        $pagetitle = !empty($otherid) ?'他的关注': '我的关注';
        $liststyle = "myfollow-list";
        break;
    case 'fans' :
        $pagetitle = !empty($otherid) ?'他的粉丝': '我的粉丝';
        $liststyle = "myfollow-list";
        break;
    case 'userexe' :
        $pagetitle = !empty($otherid) ?'他领取的任务': '我领取的任务';
        $liststyle = "myfollow-list";
        break;
    case 'userpubed' :
        $pagetitle = !empty($otherid) ?'他发布的任务': '我发布的任务';
        $liststyle = "zc-index-card";
        break;
    case 'myreport' :
        $pagetitle = !empty($otherid) ?'他的试验报告': '我的试验报告';
        $liststyle = "zc-live-card";
        break;
    case 'love' :
        $pagetitle = !empty($otherid) ?'他收藏的任务': '我收藏的任务';
        $liststyle = "zc-index-card";
        break;
}

$initParams = array(
    'title' => "我的",
    'insertelem' => $insertelem,
    'leastdraw' => $this->module['config']['leastdraw'],
    'deposit' => $this->module['config']['deposit']
);

include $this->template('mine');
?>