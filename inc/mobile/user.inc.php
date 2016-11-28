<?php
    global $_W, $_GPC;
    $userinfo = model_user::initUserInfo(); //用户信息

    if(!empty($_GPC['otherid'])){
        $where = array("uid"=>$_GPC['otherid']);
        $userinfo = model_user::getSingleUserInfo($where);
    }



    $followcnt = medoo()->count("zb_task_follow",[
        'AND'=>[
            'uniacid' => $this->conf->uniacid, 'uid' => $userinfo['uid'],
        ],
    ]);
    $fanscnt = medoo()->count("zb_task_follow",[
        'AND'=>[
            'uniacid' => $this->conf->uniacid, 'fid' => $userinfo['uid'],
        ],
    ]);;

    $_GPC['op'] =  'default';

    $initParams = array(
        'title' => '用户中心',
        'city' => $userinfo['city'],
        'insertelem' => $insertelem,
        'leastdraw' => $this->module['config']['leastdraw'],
        'deposit' => $this->module['config']['deposit']
    );
    include $this->template('user');
?>