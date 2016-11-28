<?php
global $_W, $_GPC;
$userinfo = model_user::initUserInfo(); //用户信息
$checktype = $_GPC["op"];

$insertelem = '.m-mine-list';

switch ($checktype) {
    case 'checkmanu' :
        $liststyle = "check-factory";
        break;
    case 'checkmemb' :
        $liststyle = " check-vip";
        break;
    case 'checktask' :
        $liststyle = "check-task ";
        break;
    case 'checkreport' :
        $liststyle = "myfollow-list";
        break;
}


$initParams = array(
    'title' => "待审核",
    'insertelem' => $insertelem,
    'leastdraw' => $this->module['config']['leastdraw'],
    'deposit' => $this->module['config']['deposit']
);

include $this->template('check');
?>