<?php

trait comment
{

    public function doMobileComment()
    {
        global $_W;
        $system    = $this->module['config'];
        $fans      = $this->checkinfo();
        $replyname = $this->request->reply_name;
        $datafrom  = $this->request->datafrom;
        $sectionid = $this->request->sectionid;
        $wx_share  = [
            'stitle'   => '',
            'sdesc'    => '',
            'slink'    => $_W['siteroot'] . 'app/' . $this->createMobileUrl('comment'),
            'simgUrl'  => '',
            'hideMenu' => intval(1),
        ];
        $section   = pdo_fetch("SELECT * FROM " . tablename($this->sectiontable) . "  WHERE id ='{$sectionid}'");
        $topicid   = $section['topicid'];

        $noteadd = intval($system['noteadd']);
        include $this->template('comment');
    }

    public function doMobileCommitconten()
    {
        global $_GPC, $_W;
        $system = $this->module['config'];
        $fans   = $this->checkinfo();
        if(empty($fans['openid']) || empty($fans['nickname'])) {
            $this->renderJs(["errcode" => 1, "errmsg" => 'un auth']);
        }
        $sectionid = $_GPC['sectionid'];
        $content   = $_GPC['content'];
        if(empty($content)) {
            $this->renderJs(["errcode" => 1, "errmsg" => '内容不可为空']);
        }
        if(empty($sectionid)) {
            $this->renderJs(["errcode" => 2, "errmsg" => '帖子ID不能为空']);
        }
        if($system['badword'] == 2) {
            require __DIR__ . '/../badword.src.php';
            $badword1 = array_combine($badword, array_fill(0, count($badword), '*'));
            $content  = strtr($content, $badword1);
        }


        $toid   = $_GPC['toid'];
        $toname = $_GPC['toname'];
        $nUin   = $_GPC['nUin'];
        $at     = $_GPC['at'];
        $data   = ['uniacid' => $_W['uniacid'], 'sectionid' => $sectionid, 'datato' => $toid, 'toname' => $toname, 'datafrom' => $nUin, 'nickname' => $fans['nickname'], 'content' => $_GPC['content_bak'], 'addtime' => date("Y-m-d H:i:s")];
        pdo_insert($this->replytable, $data);
        $id = pdo_insertid();

        if($system['syspl'] > 0) {
            load()->model('mc');
            $uid = $this->getUid();
            if($uid > 0) {
                $jifenresult = mc_credit_update($uid, 'credit1', $system['syspl']);
            }
        }

        if($system['noticeopen'] == 2) {
            $messageTitle = $this->getMessageTitle($sectionid);
            $toopenid     = 0;
            if($toid == 0) {
                $section = pdo_fetch("SELECT fansid,openid FROM " . tablename($this->sectiontable) . " WHERE id = '{$sectionid}'");
                if($section['fansid'] != $fans['id']) {
                    $toopenid = $section['openid'];
                    $this->sendNotice($sectionid, $toopenid, $fans['nickname'], $content, $messageTitle);
                }
            } else {
                $tofans   = pdo_fetch("SELECT openid FROM " . tablename($this->fanstable) . " WHERE id = '{$toid}' and uniacid ='{$_W['uniacid']}' ");
                $toopenid = $tofans['openid'];
                $this->sendNotice($sectionid, $toopenid, $fans['nickname'], $content, $messageTitle);
            }
            $this->notifySomebody($at, $sectionid, $content, $fans['nickname'], $toopenid, $messageTitle);//at评论用戶
        }
        $result = ["errcode" => 0, "errmsg" => "success", 'id' => $id, "data" => ["oCommentInfoPo" => ["lCommentId" => $id, "lFromId" => $nUin, "lToId" => $toid, "strContent" => $content, "lAddTime" => 1461837681]]];
        $this->renderJs($result);
    }

    protected function getMessageTitle($sectionId)
    {
        $topicId = medoo()->get($this->sectiontable, '*', ['id' => $sectionId,])['topicid'];
        if(!empty($topicId)) {
            $topicTitle = medoo()->get($this->topictable, '*', ['id' => $topicId,])['stitle'];
        } else {
            $topicTitle = medoo()->get($this->sectiontable, '*', ['id' => $sectionId,])['sharetitle'];
        }
        return "收到来自（{$topicTitle}）的一条回复";
    }


    protected function notifySomebody($row, $sectionId, $content, $nickname, $exceptOpenid, $messageTitle)
    {
        $openids = explode(',', $row);
        if(empty($openids)) return;
        $openids = array_unique($openids);
        $openids = array_map('trim', $openids);
        array_map(function($openid) use ($sectionId, $content, $nickname, $exceptOpenid, $messageTitle) {
            if($openid == $exceptOpenid) return;
            $this->sendNotice($sectionId, $openid, $nickname, $content, $messageTitle);
        }, $openids);
    }

    public function doMobileDelreply()
    {
        global $_W, $_GPC;
        $fans    = $this->checkinfo();
        $replyid = intval($_GPC['replyid']);

        if(empty($fans['openid'])) {
            $this->renderJs(["errcode" => 1, "errmsg" => '未登录，请先登录']);
        }
        if(empty($replyid)) {
            $this->renderJs(['errcode' => 1, 'topic_id' => 0, 'errmsg' => '未传入评论ID']);
        }

        $reply = pdo_fetch("SELECT * FROM " . tablename($this->replytable) . " WHERE id= '{$replyid}'");
        if(!$reply) {
            $this->renderJs(['errcode' => 1, '$replyid' => 0, 'errmsg' => '评论不存在']);
        }
        pdo_delete($this->replytable, ['id' => $replyid]);

        $this->renderJs(['errcode' => 0, 'replyid' => $replyid, 'errmsg' => '删除成功']);
    }

    /**
     * @关注人，提醒谁看页面
     */
    public function doMobileRemind()
    {
        global $_GPC, $_W;
        $system             = $this->module['config'];
        $renew              = $_GPC['renew'];
        $fans               = $this->checkinfo($renew);
        $topicid            = intval($_GPC['topicid']);
        $followers          = $this->guestList($topicid, 1);
        $topicFollowersInfo = $followers['topicFollowersInfo'];
        $followersDetail    = $followers['followersDetail'];

        if(empty($topicid)) {
            $Fanslist = pdo_fetchall("SELECT `nickname`,`headimgurl`,`openid`,`state`  FROM " . tablename($this->fanstable) . " WHERE  `state` = 2 AND `uniacid` = {$_W['uniacid']}");
        }
        include $this->template('remind');
    }
    
    public function dowebReply()
    {
        global $_W, $_GPC;
        $pageNumber = max(1, intval($_GPC['page']));
        $pageSize   = 100;
        $condition  = " and 1 = 1";
        if($state == "top") {
            $condition .= " and settop = 2 ";
        }
        $keyword = $_GPC['keyword'];
        if(!empty($keyword)) $condition .= " and content like '%{$keyword}%'";
        $sql   = "SELECT * FROM " . tablename($this->replytable) . " WHERE uniacid = '{$_W['uniacid']}' {$condition} ORDER BY id desc LIMIT " . ($pageNumber - 1) * $pageSize . ',' . $pageSize;
        $list  = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->replytable) . " WHERE uniacid = '{$_W['uniacid']}' {$condition} ORDER BY id desc");
        $pager = pagination($total, $pageNumber, $pageSize);
        include $this->template('reply');
    }

    public function dowebaddReply()
    {
        global $_W, $_GPC;
        load()->func('tpl');
        $replyid = intval($_GPC['replyid']);
        if($replyid > 0) {
            $reply = pdo_fetch("SELECT * FROM " . tablename($this->replytable) . " WHERE id= '{$replyid}'");
            if(!$reply) {
                message('抱歉，信息不存在或是已经删除！', '', 'error');
            }
        }
        if($_GPC['op'] == 'delete') {
            $reply = pdo_fetch("SELECT id FROM " . tablename($this->replytable) . " WHERE id = '{$replyid}'");
            if(empty($reply['id'])) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_delete($this->replytable, ['id' => $replyid]);
            message('删除成功！', referer(), 'success');
        }
        include $this->template('addreply');
    }
}