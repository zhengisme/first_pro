<?php

trait fans
{
    protected function addFans($info)
    {
        global $_W;
        if(empty($info['nickname']) || empty($info['openid'])) return [];
        $addTime   = date("Y-m-d H:i:s", TIMESTAMP);
        $fansTable = tablename($this->fanstable);
        medoo()->query("
            insert into {$fansTable} 
            set uniacid = '{$_W['uniacid']}' , openid = '{$info['openid']}', nickname = '{$info['nickname']}', headimgurl = '{$info['headimgurl']}', addtime = '{$addTime}'
            on duplicate key update nickname = '{$info['nickname']}', headimgurl = '{$info['headimgurl']}', updatetime = '{$addTime}'
        ");
        return medoo()->get($this->fanstable, '*', ['AND' => ['uniacid' => $_W['uniacid'], 'openid' => $info['openid'],],]);
    }

    public function doWebFans()
    {
        global $_W, $_GPC;
        $system     = $this->module['config'];
        $fans       = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE uniacid = '{$_W['uniacid']}' and status = 1 ");
        $pageNumber = max(1, intval($_GPC['page']));
        $pageSize   = 100;
        $condition  = " and 1 = 1";
        $state      = intval($_GPC['state']);
        $condition .= " and state = " . $state;
        $nickname = $_GPC['nickname'];
        if(!empty($nickname)) {
            $condition .= " AND nickname like '%{$nickname}%'";
        }
        $sql   = "SELECT * FROM " . tablename($this->fanstable) . " WHERE uniacid = '{$_W['uniacid']}'  {$condition} ORDER BY state desc,id desc LIMIT " . ($pageNumber - 1) * $pageSize . ',' . $pageSize;
        $list  = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->fanstable) . " WHERE uniacid = '{$_W['uniacid']}' {$condition} ORDER BY state desc,id");
        $pager = pagination($total, $pageNumber, $pageSize);
        include $this->template('fans');
    }

    public function doWebAddFans()
    {
        global $_W, $_GPC;
        load()->func('tpl');
        $fansid = intval($_GPC['fansid']);
        if($fansid > 0) {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(!$fans) {
                message('抱歉，信息不存在或是已经删除！', '', 'error');
            }
        }
        if($_GPC['op'] == 'tixian') {
            return $this->fanstransfer($fans);
        }
        if($_GPC['op'] == 'yunyingtixian') {
            return $this->yunyingtransfer();
        }
        if($_GPC['op'] == 'delete') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_delete($this->fanstable, ['id' => $fansid]);
            message('删除成功！', referer(), 'success');
        }
        if($_GPC['op'] == 'admin') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_update($this->fanstable, ['state' => 2], ['id' => $fansid]);
            message('设置管理员成功！', referer(), 'success');
        }
        if($_GPC['op'] == 'unadmin') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_update($this->fanstable, ['state' => 0, 'status' => 0], ['id' => $fansid]);
            message('移除管理员成功！', referer(), 'success');
        }
        if($_GPC['op'] == 'black') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_update($this->fanstable, ['state' => 1], ['id' => $fansid]);
            message('拉黑成功！', referer(), 'success');
        }
        if($_GPC['op'] == 'unblack') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_update($this->fanstable, ['state' => 0], ['id' => $fansid]);
            message('移除黑名单成功！', referer(), 'success');
        }
        if($_GPC['op'] == 'rewardper') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_update($this->fanstable, ['status' => 0], ['uniacid' => $_W['uniacid'], 'status' => 1]);
            pdo_update($this->fanstable, ['status' => 1], ['id' => $fansid]);
            message('设置运营者成功！', referer(), 'success');
        }
        if($_GPC['op'] == 'unrewardper') {
            $fans = pdo_fetch("SELECT * FROM " . tablename($this->fanstable) . " WHERE id= '{$fansid}'");
            if(empty($fans)) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_update($this->fanstable, ['status' => 0], ['id' => $fansid]);
            message('移除运营者成功！', referer(), 'success');
        }
        include $this->template('addfans');
    }
    

    protected function getFansInfoByUid($uid)
    {
        return medoo()->get($this->fanstable, '*', ['id' => $uid,]);
    }


    public function updateFansInfo($fans)
    {
        $result = medoo()->update($this->fanstable,
            ["nickname" => $fans['nickname'], "headimgurl" => $fans['headimgurl'], "updatetime" => date('Y-m-d H:i:s', TIMESTAMP),],
            ['AND' => ["uniacid" => $fans['uniacid'], "openid" => $fans['openid'],],]
        );
        return $result;
    }

    /**
     * 获取所有管理者
     * @return array
     */
    protected function getAllAdmins()
    {
        $data = medoo()->select($this->fanstable, ['id', 'nickname'], [
            'AND' => ['uniacid' => $this->conf->uniacid, 'state' => 2,],
        ]) ?: [];
        return array_column($data, 'nickname', 'id');
    }

    public function doMobileRenewFansInf()
    {
        $response = ['errcode' => 1, 'message' => '未传入openid'];
        if(null == $this->request->fans_openid) {
            $this->renderJs($response);
        }
        $oauthAccount = WeAccount::create($this->conf->account['oauth']);
        $fans         = $oauthAccount->fansQueryInfo($this->request->fans_openid);
        if(isset($fans['errno'])) {
            $this->renderJs(array_merge(['errcode' => 2,], $fans));
        }
        $this->updateFansImage($fans['openid'], $fans['headimgurl']);
        return $this->renderJs(['errcode' => 0, 'openid' => $fans['openid'], 'headimgurl' => $fans['headimgurl'],]);
    }

    protected function updateFansImage($openId, $headImageUrl)
    {
        array_map(function($table) use ($openId, $headImageUrl) {
            medoo()->update($table, ['headimgurl' => $headImageUrl,], ['openid' => $openId,]);
        }, [$this->fanstable, $this->liketable, $this->ordertable, $this->sectiontable,]);
    }
}