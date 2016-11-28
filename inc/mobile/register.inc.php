<?php
    global $_W,$_GPC;
    $userinfo = model_user::initUserInfo(); //用户信息

    $puburl = $this->createMobileUrl('pub',array('op'=>'pub'));
    $pubedurl = $this->createMobileUrl('user',array('op'=>'userpubed'));

    $reg_type = 1; //1 注册会员 2 注册厂家 3输入厂家信息
    $pagetitle = '注册会员';

    //判断是否已经绑定手机号
    if($_GPC['op'] == 'manu'){
        $pagetitle = '注册厂家';
        $reg_type = 2;
        if(!empty($userinfo['mobile'])){
            $reg_type = 3;
        }
    }
    //TODO 前期固定数据，后期后台配置
    $pubParam = Util::$staticpubParam;


    $sysdata = date("Y-m-d");
    $initParams = array(
        'title' => '注册',
        'insertelem' => ''
    );

    include $this->template('register');

?>