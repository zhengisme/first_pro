<?php
global $_W,$_GPC;
$userinfo = model_user::initUserInfo(); //用户信息
$_GPC['op'] = 'tasklive';
$initParams = array(
    'title' => '试验报告',
    'insertelem' =>'.zc-live-card'
);
$wx_share  = [
    'stitle'    => '试验报告',
    'sdesc'     => $_W['account']["name"],
    'slink'     => $_W['siteroot'] . 'app/' . $this->createMobileUrl('index'),
    'simgUrl'   => $_W['attachurl']."/headimg_".$_W['uniacid'].".jpg",
    'hideMenu'  => intval(0),
];


include $this->template('tasklive');
?>