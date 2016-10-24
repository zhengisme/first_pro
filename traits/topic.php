<?php

trait topic
{
    // ---------------------------------------   web
    public function doWebTopic()
    {
        global $_W, $_GPC;
        $pageNumber = max(1, intval($_GPC ['page']));
        $tmparr     = $_GET;
        $pageSize   = 20;
        //搜话题
        $sql  = "SELECT * FROM " . tablename($this->topictable) . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY sindex LIMIT " . ($pageNumber - 1) * $pageSize . ',' . $pageSize;
        $list = pdo_fetchall($sql);
        // 娱乐八卦不分主持人和关注人
        // 根据list的id获取主持人，关注人数，浏览量
        $topicids = array_filter(array_unique(array_column($list, 'id')));
        if(!empty ($topicids)) {
            $topicids = '(' . implode(',', $topicids) . ')';
            //$sql_host = "SELECT u.nickname,f.topicid FROM " . tablename ( $this->topicfollowedtable ) . " AS f LEFT JOIN " . tablename ( $this->fanstable ) . " AS u ON f.uid=u.id WHERE f.uniacid = '{$_W['uniacid']}' AND f.topicid IN {$topicids} AND f.guest=2";
            //$sql_follow = "SELECT COUNT(*) as count,topicid FROM " . tablename ( $this->topicfollowedtable ) . " WHERE uniacid = '{$_W['uniacid']}' AND `topicid` IN {$topicids} AND `guest` in (0,1) GROUP BY `topicid`";
            $sql_stat = "SELECT count,topicid FROM " . tablename($this->topicstattable) . " WHERE uniacid = '{$_W['uniacid']}' AND `sectionid`=0 AND `topicid` IN {$topicids}";
            //$topichost = pdo_fetchall ( $sql_host, array (), 'topicid' );
            //$topicfollow = pdo_fetchall ( $sql_follow, array (), 'topicid' );
            $topicstat = pdo_fetchall($sql_stat, [], 'topicid');

            //帖子数
            $sectionstat = pdo_fetchall("SELECT topicid,count(*) AS count FROM " . tablename($this->sectiontable) . " WHERE uniacid = '{$_W['uniacid']}' GROUP BY topicid", [], 'topicid');
            // 重新整合$list
            foreach($list as &$val) {
                /*
                $val['host']=$topichost[$val['id']]['nickname']?$topichost[$val['id']]['nickname']:'';
                $val['follow']=$topicfollow[$val['id']]['count']?$topicfollow[$val['id']]['count']:0;
                $val['stat']=$topicstat[$val['id']]['count']?$topicstat[$val['id']]['count']:0;
                */
                $sectionsum        = $sectionstat[$val['id']]['count'];
                $val['sectionsum'] = $sectionsum ? $sectionsum : 0;
            }
        }

        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->topictable) . " WHERE uniacid = '{$_W['uniacid']}'");
        $pager = pagination($total, $pageNumber, $pageSize);

        $excelurl = str_replace("topic", "topictoexcel", $_W['script_name']) . "?" . http_build_query($tmparr);
        if(preg_match("/eid=\w+/", $excelurl, $match)) {
            $excelurl = preg_replace("/eid=\w+/", "do=topictoexcel&m=ttq_ylbg", $excelurl);
        }
        include $this->template('topic');
    }

    /**生成excel
     *
     */
    public function doWebTopicToExcel()
    {
        global $_W, $_GPC;
        $pageNumber = max(1, intval($_GPC ['page']));
        $pageSize   = 20;

        $sql  = "SELECT * FROM " . tablename($this->topictable) . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY sindex LIMIT " . ($pageNumber - 1) * $pageSize . ',' . $pageSize;
        $list = pdo_fetchall($sql);

        // 根据list的id获取主持人，关注人数，浏览量
        $topicids = array_filter(array_unique(array_column($list, 'id')));
        if(!empty ($topicids)) {
            $topicids = '(' . implode(',', $topicids) . ')';
            //$sql_host = "SELECT u.nickname,f.topicid FROM " . tablename ( $this->topicfollowedtable ) . " AS f LEFT JOIN " . tablename ( $this->fanstable ) . " AS u ON f.uid=u.id WHERE f.uniacid = '{$_W['uniacid']}' AND f.topicid IN {$topicids} AND f.guest=2";
            //$sql_follow = "SELECT COUNT(*) as count,topicid FROM " . tablename ( $this->topicfollowedtable ) . " WHERE uniacid = '{$_W['uniacid']}' AND `topicid` IN {$topicids} AND `guest` in (0,1) GROUP BY `topicid`";
            $sql_stat = "SELECT count,topicid FROM " . tablename($this->topicstattable) . " WHERE uniacid = '{$_W['uniacid']}' AND `sectionid`=0 AND `topicid` IN {$topicids}";
            //$topichost = pdo_fetchall ( $sql_host, array (), 'topicid' );
            //$topicfollow = pdo_fetchall ( $sql_follow, array (), 'topicid' );
            $topicstat = pdo_fetchall($sql_stat, [], 'topicid');
            // 重新整合$list
            foreach($list as &$val) {
                //$val['host']=$topichost[$val['id']]['nickname']?$topichost[$val['id']]['nickname']:'';
                //$val['follow']=$topicfollow[$val['id']]['count']?$topicfollow[$val['id']]['count']:0;
                $val['stat'] = $topicstat[$val['id']]['count'] ? $topicstat[$val['id']]['count'] : 0;
            }
        }
        $data = [];
        $tmp  = [];
        foreach($list as $val) {
            $tmp['stitle'] = $val['stitle'];
            $tmp['sdesc']  = $val['sdesc'];
            //$tmp['host']=$val['host'];
            //$tmp['follow']=$val['follow'];
            $tmp['stat']       = $val['stat'];
            $tmp['sectionnum'] = $this->getSectionSumBySid($val['id']);
            $data[]            = $tmp;
        }

        $this->toExcel($data, ['话题标题', '话题描述', '浏览量', '发帖数量'], '话题相关统计');
    }

    public function doWebAddTopic()
    {
        global $_W, $_GPC;
        load()->func('tpl');
        $topicid = intval($_GPC['topicid']);
        if($topicid > 0) {
            $topic = pdo_fetch("SELECT * FROM " . tablename($this->topictable) . " WHERE id= '{$topicid}'");
            if(!$topic) {
                message('抱歉，信息不存在或是已经删除！', '', 'error');
            }
            $topicurl = $_W['siteroot'] . 'app/' . $this->createMobileUrl('topic', ['topicid' => $topicid]);
        }
        if($_GPC['op'] == 'delete') {
            $topic = pdo_fetch("SELECT id FROM " . tablename($this->topictable) . " WHERE id = '{$topicid}'");
            if(empty($topic['id'])) {
                message('抱歉，信息不存在或是已经被删除！');
            }
            pdo_delete($this->sectiontable, ['topicid' => $topicid]);
            pdo_delete($this->topictable, ['id' => $topicid]);
            message('删除成功！', referer(), 'success');
        }
        if(checksubmit()) {
            $data = [
                'sindex' => intval($_GPC['sindex']),
                'stitle' => $_GPC['stitle'],
                'sdesc'  => $_GPC['sdesc'],
                'simg'   => $_GPC['simg'],
                'hot'    => intval($_GPC['hot']),
                'new'    => intval($_GPC['new']),
                'state'  => intval($_GPC['state']),
            ];
            if(!empty($topicid)) {
                pdo_update($this->topictable, $data, ['id' => $topicid]);
            } else {
                $data['uniacid'] = $_W['uniacid'];
                pdo_insert($this->topictable, $data);
                $topicid = pdo_insertid();
            }
            $topic = pdo_fetch("SELECT * FROM " . tablename($this->topictable) . " WHERE id = '{$topicid}'");
            message('更新成功！', referer(), 'success');
        }
        include $this->template('addtopic');
    }

}