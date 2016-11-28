<?php
global $_W, $_GPC;
$userinfo = model_user::initUserInfo(); //用户信息
$_GPC['op'] = empty($_GPC['op']) ? 'money' : $_GPC['op'];
switch ($_GPC['op']) {
    case 'money' :
        $pagetitle = '账户余额';
        $insertelem = '.m-own-record';
        break;
    case 'addmoney' :
        $pagetitle = '账户余额';
        break;
    case 'cutmoney' :
        $pagetitle = '账户余额';
        break;
}

$initParams = array(
    'title' => '用户中心',
    'insertelem' => $insertelem,
    'leastdraw' => $this->module['config']['leastdraw'],
    'deposit' => $this->module['config']['deposit']
);
include $this->template('mypurse');
?>