<?php
global $_GPC, $_W;
$op    = empty($_GPC['op']) ? 'list' : $_GPC['op'];
$roles = ['粉丝', '会员', '厂家', '运营'];
//黑名单
//if(checksubmit('blacklist')) {
//    AllDealFunc($_GPC['uidlist'], 'changeUserStatusTo2', '');
//}
//普通粉丝
if(checksubmit('fans')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserToFans', '');
}

//会员
if(checksubmit('member')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserToMember', $this);
}

//厂家
if(checksubmit('vendor')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserToVendor', $this);
}

//运营者
if(checksubmit('operator')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserToOperator', '');
}

//恢复正常
//if(checksubmit('getright')) {
//    AllDealFunc($_GPC['uidlist'], 'changeUserStatusTo0', '');
//}

//改变余额
if(checksubmit('credit2')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserCredit', $_GPC['changevalue']);
}

//改变保证金
if(checksubmit('doposit')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserDeposit', $_GPC['changevalue']);
}

//改变信誉积分
if(checksubmit('creditscore')) {
    AllDealFunc($_GPC['uidlist'], 'changeUserScroe', $_GPC['changevalue']);
}


if($op == 'list') {
    if($_GPC['item'] == 'deposit') $where['deposit>'] = 0.01;
    if($_GPC['item'] == 'status2') $where['status'] = 2;
    if($_GPC['item'] == 'all') $where = [];

    if($_GPC['item'] == 'state0') $where['state'] = 0;
    if($_GPC['item'] == 'state1') $where['state'] = 1;
    if($_GPC['item'] == 'state2') $where['state'] = 2;
    if($_GPC['item'] == 'state3') $where['state'] = 3;


    if($_GPC['orderby'] == 'pub') $order = 'pubnumber';
    if($_GPC['orderby'] == 'reply') $order = 'replynumber';
    if($_GPC['orderby'] == 'deposit') $order = 'deposit';
    if($_GPC['orderby'] == 'credit2') $order = 'credit2';
    if(empty($_GPC['orderby'])) $order = 'id';

    $seq = empty($_GPC['seq']) ? 'DESC' : 'ASC';

    $user = model_user::getAllUser($where, $order, $_GPC['page'], 1, $seq);

    $userinfo = array_map(function($v) {
        $desc = json_decode($v['guydesc']);
        if(empty($desc)) return $v;
        $info = isset($desc->manupre) ? "联系人：{$desc->manupre}<br/>" : '';
        $info .= isset($desc->manuname) ? "公司名称：{$desc->manuname}<br/>" : '';
        $info .= isset($desc->manudesc) ? "备注：{$desc->manudesc}" : '';
        $v['guydesc'] = $info;
        return $v;
    }, (array)$user[0]);

    $pager = $user[1];
}

//编辑用户
if($op == 'edit') {
    $_GPC     = Util::trimWithArray($_GPC);
    $type     = intval($_GPC['type']);
    $uid      = intval($_GPC['taskuid']);
    $value    = $_GPC['money'];
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    if(empty($userinfo)) die;
    if($type == 1) $res = changeUserCredit($uid, $value); //改变余额
    if($type == 2) $res = changeUserScroe($uid, $value); //改变信誉积分
    if($type == 3) $res = changeUserDeposit($uid, $value); //改变保证金
    if($type == 4) $res = changeUserStatusTo2($uid); //加入黑名单
    if($type == 5) $res = changeUserStatusTo0($uid); //恢复正常
    if($type == 4 || $type == 5) model_user::deleteUserCache($userinfo['openid']);  //删除用户缓存
    if($res) die('1');
    die('2');
}

//搜索
if($op == 'search') {
    $for      = htmlspecialchars($_GPC['for']);
    $where    = ['nickname' => $for, 'uid' => $for];
    $user     = model_user::getAllUser($where, 'acceptnumber', $_GPC['page'], 2);
    $userinfo = $user[0];
    $pager    = $user[1];
}

//共用处理方法
function AllDealFunc($arrayuid, $funcname, $value)
{
    foreach($arrayuid as $k => $v) {
        call_user_func_array($funcname, [$v, $value]);
    }
    message('操作完成', referer(), 'success');
}

//改变信誉积分
function changeUserScroe($uid, $value)
{
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    $res      = model_user::changeUserCreditScore($value, $uid);
    if($res) $res = model_scorelog::insertScoreLog($uid, $userinfo['openid'], $value, 6);
    model_user::deleteUserCache($userinfo['openid']); //删除缓存
    return $res;
}

//改变保证金
function changeUserDeposit($uid, $value)
{
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    $res      = Util::addAndMinusData('zb_task_user', ['deposit' => $value], ['uid' => $uid]);
    if($res) $res = model_depositlog::insertDepositlogData(['openid' => $userinfo['openid'], 'uid' => $userinfo['uid']], $value, 3, '其他');    //已删缓存
    return $res;
}

//改变余额
function changeUserCredit($uid, $value)
{
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    $res      = model_user::updateUserCredit2($uid, $value);
    model_user::deleteUserCache($userinfo['openid']); //删除缓存
    if($res) $res = model_moneylog::insertMoneyLog($uid, $userinfo['openid'], '', '其他', $value, 10); //插入资金记录
    return $res;
}

//转为普通粉丝
function changeUserToFans($uid)
{
    $res      = Util::updateSingleData('zb_task_user', ['state' => 0], ['uid' => $uid]);
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    model_user::deleteUserCache($userinfo['openid']); //删除缓存
    return $res;
}

//转为会员
function changeUserToMember($uid, $obj)
{
    $res      = Util::updateSingleData('zb_task_user', ['state' => 1], ['uid' => $uid]);
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    model_user::deleteUserCache($userinfo['openid']); //删除缓存
    //发送会员审核通过提醒
    $obj->notify->msgg([
        'openid' => $userinfo['openid'], 'keyword1' => ['已成为会员', 'ff0000'], 'keyword2' => '符合小桔灯平台规则', 'url' => util::createMUrl('pub'),
    ]);
    return $res;
}

//转为厂家
function changeUserToVendor($uid, $obj)
{
    $res      = Util::updateSingleData('zb_task_user', ['state' => 2], ['uid' => $uid]);
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    model_user::deleteUserCache($userinfo['openid']); //删除缓存
    //发送厂家审核通过提醒
    $obj->notify->msgf([
        'openid' => $userinfo['openid'], 'keyword1' => ['注册厂家成功', 'ff0000'], 'keyword2' => '符合小桔灯平台规则', 'url' => util::createMUrl('pub'),
    ]);
    return $res;
}

//转为运营者
function changeUserToOperator($uid)
{
    $res      = Util::updateSingleData('zb_task_user', ['state' => 3], ['uid' => $uid]);
    $userinfo = model_user::getSingleUserInfo(['uid' => $uid]);
    model_user::deleteUserCache($userinfo['openid']); //删除缓存
    return $res;
}

include $this->template('web/user');
?>