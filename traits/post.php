<?php

trait post
{

    public function doMobileSdetail()
    {
        global $_GPC, $_W;
        $system    = $this->module['config'];
        $renew     = $_GPC['renew'];
        $fans      = $this->checkinfo($renew);
        $sectionid = intval($_GPC['sectionid']);
        $fromtopic = $_GPC['fromtopic'];

        pdo_query("UPDATE " . tablename($this->sectiontable) . " SET scansum = scansum +1 WHERE id ='{$sectionid}' ");
        $section = pdo_fetch("SELECT st.*,tp.stitle FROM " . tablename($this->sectiontable) . " as st left join " . tablename($this->topictable) . " as tp on st.topicid = tp.id  WHERE st.id ='{$sectionid}'");


        $topicid = intval($section['topicid']);

        if(empty($section)) {
            $uniacid = isset($_GPC['i']) ? $_GPC['i'] : '1';
            $do      = 'home';
            $m       = isset($_GPC['m']) ? $_GPC['m'] :$this->system_modules;
            $urlArea = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . 'i=' . $uniacid . '&c=entry&do=' . $do . '&m=' . $m;
            exit("<script>location.href='" . $urlArea . "'</script>");
        }

        $creator = $this->getFansInfoByUid($section['fansid']);
        $compere = medoo()->get($this->topictable, '*', [
            'id' => $section['topicid'],
        ]);

        $sharetitle = $section['sharetitle'];
        if(empty($sharetitle)) {
            $sharetitle = mb_substr($section['content'], 0, 34, 'utf-8');
        }
        $sharedesc = $section['sharedesc'];
        if(empty($sharedesc)) {
            $sharedesc = $system['sysdesc'];
        }
        $simgs            = unserialize($section['imgs']);
        $section['extra'] = json_decode($section['extra'], true);


        $shareimg = '';
        //从contentz中取图
        $contenttemp = $section['content'];
        preg_match_all('/<img[^>]*>/', $contenttemp, $match);
        if(count($match) > 0) {
            $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
            preg_match_all($preg, $match[0][0], $imgtempArr);
            $shareimg = $imgtempArr[2][0];
        }

        if(!empty($simgs)) {
            $shareimg = $_W['attachurl'] . $simgs[0];
        }
        $section['showtime'] = strtotime($section['addtime']);
        $likesql             = "SELECT headimgurl,fansid FROM " . tablename($this->liketable) . "
									WHERE uniacid = '{$_W['uniacid']}' and sectionid = '{$section['id']}' ORDER BY id  limit 10";
        $likelist            = pdo_fetchall($likesql);
        $section['likelist'] = $likelist;

        $section['likesum'] = count($likelist);//点赞数
        if($section['likesum'] == 10) {
            $section['likesum'] = medoo()->count($this->liketable, [
                'AND' => ['uniacid' => $this->conf->uniacid, 'sectionid' => $section['id'],],
            ]);
        }

        $section['dolike'] = medoo()->has($this->liketable, [
            'AND' => ['fansid' => $fans['id'], 'uniacid' => $this->conf->uniacid, 'sectionid' => $section['id'],],
        ]);

        $replysql             = "SELECT rep.id,rep.datato,rep.toname,rep.datafrom,f.headimgurl,rep.nickname,rep.content,rep.addtime FROM " . tablename($this->replytable) . " as rep left join " . tablename($this->fanstable) . " as f on f.id = rep.datafrom WHERE rep.state = 2 and rep.uniacid = '{$_W['uniacid']}' and rep.sectionid = '{$section['id']}' ORDER BY rep.id " . $system['replysort'];
        $replylist            = pdo_fetchall($replysql);
        $section['replylist'] = $replylist;
        $section['replysum']  = count($replylist);
        $systime              = TIMESTAMP;
        $wx_share             = [
            'stitle'   => $section['sharetitle'],//去掉表情
            'sdesc'    => nl2br(html_entity_decode(strip_tags($section['sharedesc']))),
            'slink'    => $_W['siteroot'] . 'app/' . $this->createMobileUrl('sdetail', ['topicid' => $topicid, 'sectionid' => $sectionid, 'renew' => 1]),
            'simgUrl'  => $shareimg,
            'hideMenu' => intval(0),
        ];
        $this->topicStat($section['topicid'], $sectionid);

        $stateArray = $this->stat($section['topicid'], $sectionid);

        $fansAut  = '-1';
        if(($section['section_type'] == 1 || $section['section_type'] == 3) && !empty($section['section_attach'])) {
            $section['section_attach'] = json_decode($section['section_attach'], true);
        }
        if($section['section_type'] == 2) {
            $citationpost = medoo()->get($this->sectiontable, '*', [
                'id' => $section['section_attach'],
            ]);
            if(!empty($citationpost)) {
                $citationImg           = unserialize($citationpost['imgs'])[0];
                $citationpost['extra'] = json_decode($citationpost['extra'], true);
                if(!empty($citationImg) && !empty($citationpost['extra'])) {
                    foreach($citationpost['extra'] as $extraItem) {
                        if($extraItem['i'] == 0) {
                            $citationImg_width  = $extraItem['w'];
                            $citationImg_height = $extraItem['h'];
                            break;
                        }
                    }
                } else {
                    $cit_content = $citationpost['content'];
                    preg_match_all('/<img[^>]*\>/', $cit_content, $match);
                    if(count($match) > 0) {
                        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
                        preg_match_all($preg, $match[0][0], $imgArr);
                        $citationImg     = $imgArr[2][0];
                        $citationImg_all = 1;
                    }
                }
            }
        }

        $system['sysguideurl'] = $_SERVER['HTTP_REFERER'];
        include $this->template('sdetail');
    }

    public function doMobileGetcitationpost()
    {
        global $_GPC, $_W;
        $sectionid = $this->request->sectionid;
        if(empty($sectionid)) {
            $result = ["errcode" => 1, "errmsg" => "未传入文章ID"];
        }
        $citationpost = medoo()->get($this->sectiontable, '*', [
            'id' => $sectionid,
        ]);
        if(empty($citationpost)) {
            $result = ["errcode" => 1, "errmsg" => "文章不存在"];
        }
        $result                = ["errcode" => 0, "errmsg" => "success", 'citationpost' => $citationpost];
        $citationImg           = unserialize($citationpost['imgs'])[0];
        $citationpost['extra'] = json_decode($citationpost['extra'], true);
        if(!empty($citationImg) && !empty($citationpost['extra'])) {
            foreach($citationpost['extra'] as $extraItem) {
                if($extraItem['i'] == 0) {
                    $citationImg_width            = $extraItem['w'];
                    $citationImg_height           = $extraItem['h'];
                    $result['citationImg_width']  = $citationImg_width;
                    $result['citationImg_height'] = $citationImg_height;
                    break;
                }
            }
        } else {
            $cit_content = $citationpost['content'];
            preg_match_all('/<img[^>]*\>/', $cit_content, $match);
            if(count($match) > 0) {
                $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
                preg_match_all($preg, $match[0][0], $imgArr);
                $citationImg               = $imgArr[2][0];
                $result["citationImg_all"] = 1;
            }
        }
        $citationcontent           = html_entity_decode(strip_tags($citationpost['content']));
        $result['citationcontent'] = $citationcontent;
        $result['citationImg']     = $citationImg;
        $this->renderJs($result);
    }

    /**
     * 给贴子加上高宽
     */
    public function doMobileUpdateExtra()
    {
        $sectionid = $this->request->sectionid;
        if(empty($sectionid)) {
            $this->renderJs(["errcode" => 1, "errmsg" => '示传入帖子ID']);
        }

        $section = medoo()->get($this->sectiontable, '*', ['id' => $sectionid]);
        if(empty($section)) {
            $this->renderJs(["errcode" => 1, "errmsg" => '帖子已删除']);
        }
        if($section['section_type'] == 0) {
            $extra      = json_decode($section['extra'], true);
            $index      = $this->request->img_index;
            $img_width  = $this->request->img_width;
            $img_height = $this->request->img_height;
            $extra_item = ['i' => $index, 'w' => $img_width, 'h' => $img_height,];
            if(empty($extra)) {
                $extra = [(object)$extra_item];
            } else {
                $isHave = 0;
                foreach($extra as $index => $row) {
                    if($row['i'] == $index) {
                        $isHave = $index;
                        break;
                    }
                }
                if($isHave == 0) {
                    array_push($extra, (object)$extra_item);
                } else {
                    $extra[$isHave] = (object)$extra_item;
                }
            }
            $res = medoo()->update($this->sectiontable, ['extra' => json_encode($extra),], ['id' => $sectionid]);
            $this->renderJs(["errcode" => 0, "errmsg" => 'json_encode($extra)']);
        }

        if($section['section_type'] == 3) {
            $section_attach           = json_decode($section['section_attach'], true);
            $img_width                = $this->request->img_width;
            $img_height               = $this->request->img_height;
            $section_attach['width']  = $img_width;
            $section_attach['height'] = $img_height;
            $res                      = medoo()->update($this->sectiontable, ['section_attach' => json_encode($section_attach),], ['id' => $sectionid]);
            $this->renderJs(["errcode" => 0, "errmsg" => 'success']);
        }


    }

    public function doMobileDolike()
    {
        global $_GPC, $_W;
        $system = $this->module['config'];
        $fans   = $this->checkinfo();
        if(empty($fans['openid']) || empty($fans['nickname'])) {
            $this->renderJs(["errcode" => 1, "errmsg" => 'un auth']);
        }
        $sectionid   = $_GPC['sectionid'];
        $operatetype = intval($_GPC['operatetype']);
        if($operatetype == 1) {
            $likeobj = medoo()->get($this->liketable, '*', [
                'AND' => [
                    'sectionid' => $sectionid,
                    'fansid'    => $fans['id'],
                    'uniacid'   => $_W['uniacid'],
                ],
            ]);
            if(empty($likeobj)) {
                $data = ['uniacid' => $_W['uniacid'], 'sectionid' => $sectionid, 'openid' => $fans['openid'], 'fansid' => $fans['id'], 'headimgurl' => $fans['headimgurl'], 'nickname' => $fans['nickname'], 'addtime' => date("Y-m-d H:i:s")];
                pdo_insert($this->liketable, $data);
                $id = pdo_insertid();

                if($system['syszan'] > 0) {
                    load()->model('mc');
                    $uid = $_W['member']['uid'];
                    if($uid > 0) {
                        $jifenresult = mc_credit_update($uid, 'credit1', $system['syszan']);
                    }
                }
            } else {
                $result = ["errcode" => 1, "errmsg" => "已点过赞"];
            }

        } else {
            pdo_delete($this->liketable, ['sectionid' => $sectionid, 'fansid' => $fans['id']]);
            if($system['syszan'] > 0) {
                load()->model('mc');
                $uid = $_W['member']['uid'];
                if($uid > 0) {
                    $jifenresult = mc_credit_update($uid, 'credit1', $system['syszan'] * -1);
                }
            }
        }
        $result = ["errcode" => 0, "errmsg" => "success"];
        $this->renderJs($result);
    }

    /**
     * 点赞列表
     */
    public function doMobileLikelist()
    {
        global $_GPC, $_W;
        $fans = $this->checkinfo();
        if(empty($fans['openid']) || empty($fans['nickname'])) {
            die(json_encode(["errcode" => 1, "errmsg" => 'un auth']));
        }
        $sectionid = $_GPC['sectionid'];
        $sql       = "SELECT DISTINCT fansid,headimgurl,openid FROM " . tablename($this->liketable) . "  WHERE uniacid = '{$_W['uniacid']}' and sectionid = '{$sectionid}'  ORDER BY addtime desc";
        $list      = pdo_fetchall($sql);

        $likesum  = count($list);
        $likelist = [];
        foreach($list as $index => $row) {
            if($index == 8) {
                break;
            }
            $likelist[$index] = $row;

        }

        $result = ["errcode" => 0, "errmsg" => "success", 'likesum' => $likesum, "likelist" => $likelist];
        $this->renderJs($result);
    }


    public function doWebSection()
    {
        global $_W, $_GPC, $_GET;
        $pageNumber = max(1, intval($_GPC['page']));
        $pageSize   = 20;

        //取时间
        $startdate = $_GET['startdate'] ? $_GET['startdate'] : ($_GPC['time']['start'] ? $_GPC['time']['start'] : 0);
        $enddate   = $_GET['enddate'] ? $_GET['enddate'] : ($_GPC['time']['end'] ? $_GPC['time']['end'] : 0);
        //回显到page且赋值到get供给excel
        $_GET['startdate'] = $startdate;
        $_GET['enddate']   = $enddate;
        //回显到日历
        $starttime = strtotime($startdate);
        $endtime   = strtotime($enddate);
        //结束加1天
        $enddate = date('Y-m-d', $endtime + 86400);

        $state = $_GPC['state'];
        if(empty($state)) {
            $state = "all";
        }

        $condition = " 1 = 1";
        $orderby   = "";
        if($state == "top") {
            $condition .= " and settop = 2 ";
            $orderby .= " toptime desc,";
        }
        $keyword = $_GPC['keyword'];

        $topicid = $_GPC['topicid'];

        if(!empty($keyword)) {
            $condition .= " and content like '%{$keyword}%'";
        }

        if(!empty($topicid)) {
            $condition .= " AND  `topicid` = '{$topicid}'";
        }

        if($startdate && $enddate) {
            $condition .= " AND  `addtime` > '{$startdate}' AND `addtime` < '{$enddate}'";
        }

        $sql = "SELECT * FROM " . tablename($this->sectiontable) . " WHERE  {$condition} ORDER BY {$orderby} id desc LIMIT " . ($pageNumber - 1) * $pageSize . ',' . $pageSize;

        $list  = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->sectiontable) . " WHERE {$condition} ORDER BY settop desc,toptime desc,id desc");
        foreach($list as $k => &$v) {
            //去空去图
            $v['content'] = trim($v['content']);
            $v['content'] = preg_replace("/\s+/", " ", $v['content']);
            $v['content'] = preg_replace("/<img\s+.*?\/>/", "[图片]", $v['content']);
            if(mb_strlen($v['content']) > 10)
                $v['content'] = mb_substr($v['content'], 0, 10);

            if($v['topicid'] > 0) {
                $stitle      = pdo_fetch("SELECT `stitle` FROM " . tablename($this->topictable) . "WHERE `id` = {$v['topicid']}");
                $v['stitle'] = $stitle['stitle'] . '(话题)';
            } else {
                $v['stitle'] = '资讯';
            }
        }

        $topiclist = pdo_fetchall("SELECT `id`,`stitle` FROM " . tablename($this->topictable));

        //根据帖子Id获取其浏览数，点赞数，回复数
        $idarr = array_unique(array_column($list, 'id'));//获取所有id
        if(empty($idarr)) {
            //无帖子不操作
        } else {
            $idstr = "(" . implode(",", $idarr) . ")";
            //点赞数
            $sql     = "SELECT `sectionid`,COUNT(*) AS count from " . tablename($this->liketable) . " WHERE `sectionid` IN {$idstr} GROUP BY `sectionid`";
            $likearr = pdo_fetchall($sql, [], 'sectionid');
            //回复数
            $sql       = "SELECT `sectionid`,COUNT(*) AS count from " . tablename($this->replytable) . " WHERE `sectionid` IN {$idstr} GROUP BY `sectionid`";
            $replayarr = pdo_fetchall($sql, [], 'sectionid');
            //浏览数
            $sql      = "SELECT `sectionid`,`count` from " . tablename($this->topicstattable) . " WHERE `sectionid` IN {$idstr}";
            $countarr = pdo_fetchall($sql, [], 'sectionid');

            //数据整合
            foreach($list as &$val) {
                if($count = $likearr[$val['id']]['count'])
                    $val['like'] = $count;
                else
                    $val['like'] = 0;
                if($count = $replayarr[$val['id']]['count'])
                    $val['reply'] = $count;
                else
                    $val['reply'] = 0;
                if($count = $countarr[$val['id']]['count'])
                    $val['count'] = $count;
                else
                    $val['count'] = 0;
            }

        }

        $excelurl = str_replace("section", "sectiontoexcel", $_W['script_name'] . "?" . http_build_query($_GET));
        if(preg_match("/eid=\w+/", $excelurl, $match)) {
            $excelurl = preg_replace("/eid=\w+/", "do=sectiontoexcel&m=ttq_ylbg", $excelurl);
        }
        $pager = pagination($total, $pageNumber, $pageSize);
        include $this->template('section');
    }

    public function doWebSectionToExcel()
    {
        global $_W, $_GPC, $_GET;
        //取时间
        $startdate = $_GET['startdate'] ? $_GET['startdate'] : ($_GPC['time']['start'] ? $_GPC['time']['start'] : 0);
        $enddate   = $_GET['enddate'] ? $_GET['enddate'] : ($_GPC['time']['end'] ? $_GPC['time']['end'] : 0);
        //结束加1天
        $starttime = strtotime($startdate);
        $endtime   = strtotime($enddate);
        $enddate   = date('Y-m-d', $endtime + 86400);

        $state = $_GPC['state'];
        if(empty($state)) {
            $state = "all";
        }
        $condition = " and 1 = 1";
        $orderby   = "";
        if($state == "top") {
            $condition .= " and settop = 2 ";
            $orderby .= " toptime desc,";
        }
        $keyword = $_GPC['keyword'];

        $topicid = $_GPC['topicid'];

        if(!empty($keyword)) {
            $condition .= " and content like '%{$keyword}%'";
        }

        if(!empty($topicid)) {
            $condition .= " AND  `topicid` = '{$topicid}'";
        }

        if($startdate && $enddate) {
            $condition .= " AND  `addtime` > '{$startdate}' AND `addtime` < '{$enddate}'";
        }

        $sql = "SELECT * FROM " . tablename($this->sectiontable) . "
        WHERE uniacid = 1 {$condition}
        ORDER BY {$orderby} id desc";

        $list  = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->sectiontable) . "
                WHERE uniacid = 1 {$condition} ORDER BY settop desc,toptime desc,id desc");
        foreach($list as $k => &$v) {
            if($v['topicid'] > 0) {
                $stitle      = pdo_fetch("SELECT `stitle` FROM " . tablename($this->topictable) . "WHERE `id` = {$v['topicid']}");
                $v['stitle'] = $stitle['stitle'] . '(话题)';
            } else {
                $v['stitle'] = '八卦';
            }
        }

        //根据帖子Id获取其浏览数，点赞数，回复数
        $idarr = array_unique(array_column($list, 'id'));//获取所有id
        if(empty($idarr)) {
            //无帖子不操作
        } else {
            $idstr = "(" . implode(",", $idarr) . ")";
            //点赞数
            $sql     = "SELECT `sectionid`,COUNT(*) AS count from " . tablename($this->liketable) . " WHERE `sectionid` IN {$idstr} GROUP BY `sectionid`";
            $likearr = pdo_fetchall($sql, [], 'sectionid');
            //回复数
            $sql       = "SELECT `sectionid`,COUNT(*) AS count from " . tablename($this->replytable) . " WHERE `sectionid` IN {$idstr} GROUP BY `sectionid`";
            $replayarr = pdo_fetchall($sql, [], 'sectionid');
            //浏览数
            $sql      = "SELECT `sectionid`,`count` from " . tablename($this->topicstattable) . " WHERE `sectionid` IN {$idstr}";
            $countarr = pdo_fetchall($sql, [], 'sectionid');

            //数据整合
            $tmp  = [];
            $data = [];
            foreach($list as $val) {
                $tmp['id']       = $val['id'];
                $tmp['stitle']   = $val['stitle'];
                $tmp['content']  = $val['content'];
                $tmp['addtime']  = $val['addtime'];
                $tmp['nickname'] = $val['nickname'];

                if($count = $likearr[$val['id']]['count'])
                    $tmp['like'] = $count;
                else
                    $tmp['like'] = 0;
                if($count = $replayarr[$val['id']]['count'])
                    $tmp['reply'] = $count;
                else
                    $tmp['reply'] = 0;
                if($count = $countarr[$val['id']]['count'])
                    $tmp['count'] = $count;
                else
                    $tmp['count'] = 0;
                $data[] = $tmp;
            }
        }

        $this->toExcel($data, ['序号 ', '帖子类型', '帖子内容', '发帖时间 ', '发帖人', '点赞', '回复 ', '浏览'], "帖子数据");
    }

    public function doWebAddSection()
    {
        global $_W, $_GPC;
        load()->func('tpl');
        $sectionid = (int)$this->request->sectionid;
        $topicid   = (int)$this->request->topicid;

        if($sectionid > 0) {
            $section = medoo()->get($this->sectiontable, '*', ['id' => $sectionid,]);

            if(!$section) message('抱歉，信息不存在或是已经删除！', '', 'error');
            $imgs      = unserialize($section['imgs']);
            $topiclist = medoo()->select($this->topictable, '*', ['uniacid' => $this->conf->uniacid, 'ORDER' => 'sindex',]);
        }
        if(empty($section['sharedesc'])) {
            $system               = $this->module['config'];
            $section['sharedesc'] = $system['sysdesc'];
        }
        if($topicid > 0) {
            $adminlist = medoo()->select($this->fanstable, '*', [
                'AND'   => ['uniacid' => $this->conf->uniacid, 'state' => 2,],
                'ORDER' => 'id desc',
            ]);
        }
        if($this->request->op == 'delete') {
            return $this->delSection($sectionid);
        }
        if(checksubmit()) {
            return $this->addSection($section, $sectionid);
        }
        $opera   = "编辑";
        include $this->template('addsection');
    }

    protected function delSection($id)
    {
        $where = ['AND' => ['sectionid' => $id, 'uniacid' => $this->conf->uniacid,],];
        medoo()->delete($this->liketable, $where);
        medoo()->delete($this->replytable, $where);
        medoo()->delete($this->sectiontable, ['AND' => ['id' => $id, 'uniacid' => $this->conf->uniacid,],]);
        return message('删除成功！', referer(), 'success');
    }

    protected function addSection($section, $sectionid)
    {
        $imgs    = serialize($this->request->imgs);
        $settop  = (int)$this->request->settop;
        $topicid = (int)$this->request->topicid;
        $tag     = is_array($this->request->tag) ? implode(",", $this->request->tag) : '';

        $data = [
            'content'   => htmlspecialchars_decode($this->request->content),
            'extra'     => '', 'imgs' => $imgs, 'settop' => $settop,
            'topicid'   => $topicid, 'sharetitle' => $this->request->sharetitle,
            'sharedesc' => $this->request->sharedesc,
            'scansum'   => (int)$this->request->scansum,
            'state'     => (int)$this->request->state,
            'quote'     => (int)$this->request->quote,
            'tag'       => $tag,
        ];


        if(!empty($sectionid)) {
            $data['toptime'] = $settop == 2 ? date('Y-m-d H:i:s', TIMESTAMP) : $section['addtime'];
            medoo()->update($this->sectiontable, $data, ['AND' => ['id' => $sectionid, 'uniacid' => $this->conf->uniacid,],]);
            return message('更新成功！', referer(), 'success');
        }

        $data['addtime'] = $data['toptime'] = date('Y-m-d H:i:s', TIMESTAMP);
        $data['uniacid'] = $this->conf->uniacid;
        $fansid          = (int)$this->request->fansid;
        $fans            = medoo()->get($this->fanstable, '*', ['id' => $fansid,]);
        if(empty($fans)) {
            message('管理员不存在：id=' . $fansid);
        }

        $data['fansid']     = $fans['id'];
        $data['openid']     = $fans['openid'];
        $data['nickname']   = $fans['nickname'];
        $data['headimgurl'] = $fans['headimgurl'];
        $data['status']     = $fans['state'];
        medoo()->insert($this->sectiontable, $data);
        return message('添加成功！', referer(), 'success');
    }


    public function getSectionSumBySid($topicid)
    {
        global $_W;
        $result = pdo_fetch("SELECT count(*) as cnt FROM " . tablename($this->sectiontable) . " WHERE topicid = '{$topicid}' and uniacid = '{$_W['uniacid']}'");
        return $result['cnt'] <= 0 ? 0 : $result['cnt'];
    }

    public function getSectionSumByFansid($fansid)
    {
        global $_W;
        $result = pdo_fetch("SELECT count(*) as cnt FROM " . tablename($this->sectiontable) . " WHERE fansid = '{$fansid}' and uniacid = '{$_W['uniacid']}'");
        return $result['cnt'] <= 0 ? 0 : $result['cnt'];
    }


    /*
 * 作物圈分页
 */
    public function doMobileZwqPage()
    {
        global $_GPC, $_W;
        $system    = $this->module ['config'];
        $renew     = $_GPC ['renew'];
        $fans      = $this->checkinfo($renew);
        $fansid    = $fans ['id'];
        $doType    = $_GPC ['state'];
        $sectionid = $this->request->sectionid;
        if('live' != $doType && 'home' != $doType) {
            $doType = 'home';
        }
        $thefans = pdo_fetch("SELECT `id`,`openid`,`nickname`,`headimgurl`,`addtime`,`signtime` FROM " . tablename($this->fanstable) . " WHERE `id` ='{$fansid}'");
        if(empty ($thefans)) {
            message('找不到粉丝数据');
        }

        //贴子定位
        if($sectionid == 0) {
            $this->renderJs(["errcode" => 1, "errmsg" => "参数错误"]);
        }

        //取话题
        $sqlcondition = '';
        $currenttopic = $_GET['currenttopic'];
        if(empty($currenttopic)) {
            $currenttopic = pdo_fetch("SELECT `id` FROM " . tablename($this->topictable) . " WHERE `state` = 2 ");
            $currenttopic = $currenttopic['id'];
        }
        $sqlcondition         = "AND `topicid`={$currenttopic}";
        $_GET['currenttopic'] = $currenttopic;

        $size = 10;
        $sql  = "SELECT * FROM ". tablename($this->sectiontable)." WHERE `state` = 2 AND `id` < {$sectionid} {$sqlcondition} ORDER BY `addtime` DESC LIMIT {$size}";

        $list      = pdo_fetchall($sql);
        $topiclist = [];
        foreach($list as $index => $row) {

            if($row ['topicid'] > 0) {
                $topicinfo = pdo_fetch("SELECT `stitle` FROM " . tablename($this->topictable) . "WHERE `id` = '{$row['topicid']}' AND `state` = 2");
                if(empty ($topicinfo))
                    continue;
                $row ['stitle'] = $topicinfo ['stitle'];
            }

            $row ['showtime'] = $this->format_date(strtotime($row ['addtime']));
            $likesql          = "SELECT `headimgurl`,`fansid` FROM " . tablename($this->liketable) . " WHERE `uniacid` IN (1,'{$_W['uniacid']}')  AND `sectionid` = '{$row['id']}' ORDER BY `id` LIMIT {$size}";
            $likelist         = pdo_fetchall($likesql);
            $row ['likelist'] = $likelist;
            if(count($likelist) < 10) {
                $row ['likesum'] = count($likelist);
            } else {
                $total           = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->liketable) . "
    					WHERE `uniacid` IN (1,'{$_W['uniacid']}') AND `sectionid` = '{$row['id']}' ORDER BY `id`");
                $row ['likesum'] = $total;
            }
            $dolike = pdo_fetch("SELECT * FROM " . tablename($this->liketable) . " WHERE `fansid` = '{$fans['id']}' AND  `uniacid` IN ('{$_W['uniacid']}',1) AND `sectionid` = '{$row['id']}'");
            if(empty ($dolike)) {
                $row ['dolike'] = 0;
            } else {
                $row ['dolike'] = 1;
            }

            $fansstate         = pdo_fetch("SELECT `state` FROM " . tablename($this->fanstable) . " WHERE `id` = {$row['fansid']}");
            $row ['fansstate'] = $fansstate ['state'];

            if($row ['section_type'] == 2 && !empty ($row ['section_attach'])) {
                $citationpost = medoo()->get($this->sectiontable, '*', [
                    'id' => $row ['section_attach'],
                ]);
                if(!empty ($citationpost)) {

                    $citationImg            = unserialize($citationpost ['imgs'])[0];
                    $citationpost ['extra'] = json_decode($citationpost ['extra'], true);
                    if(!empty ($citationImg) && !empty ($citationpost ['extra'])) {
                        foreach($citationpost ['extra'] as $extraItem) {
                            if($extraItem ['i'] == 0) {
                                $citationImg_width                   = $extraItem ['w'];
                                $citationImg_height                  = $extraItem ['h'];
                                $citationpost ['citationImg_width']  = $citationImg_width;
                                $citationpost ['citationImg_height'] = $citationImg_height;
                                break;
                            }
                        }
                    } else {
                        $cit_content = $citationpost ['content'];
                        preg_match_all('/<img[^>]*\>/', $cit_content, $match);
                        if(count($match) > 0) {
                            $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
                            preg_match_all($preg, $match [0] [0], $imgArr);
                            $citationImg                      = $imgArr [2] [0];
                            $citationpost ["citationImg_all"] = 1;
                        }
                    }
                }
                $citationpost ['content']     = html_entity_decode(strip_tags($citationpost ['content']));
                $citationpost ['citationImg'] = $citationImg;
                $row ['citationpost']         = $citationpost;
            }
            if(($row ['section_type'] == 1 || $row ['section_type'] == 3) && !empty ($row ['section_attach'])) {
                $row ['section_attach'] = json_decode($row ['section_attach'], true);
            }

            $replysql           = "SELECT `datato`,`toname`,`datafrom`,`nickname`,`content` FROM " . tablename($this->replytable) . " WHERE `state` = 2 and `uniacid` IN ('{$_W['uniacid']}',1) AND `sectionid` = '{$row['id']}' ORDER BY `id` LIMIT {$size}";
            $replylist          = pdo_fetchall($replysql);
            $row ['replylist']  = $replylist;
            $row ['replysum']   = count($replylist);
            $row ['extra']      = json_decode($row ['extra'], true);
            $row ['imgs']       = unserialize($row ['imgs']);
            $row ['imgsum']     = count($row ['imgs']);
            $topiclist [$index] = $row;
        }


        //渲染作物圈的数据
        ob_start();
        include $this->template('home_section');
        $home_contents = ob_get_contents();
        ob_end_clean();

        $lastId = end($topiclist);


        if(empty($topiclist)) {
            $result = ["errcode" => 1, "errmsg" => "fail", "data" => ""];
        } else {
            $result = ["errcode" => 0, "errmsg" => "success", "data" => $home_contents, "lastId" => $lastId['id']];
        }

        $this->renderJs($result);
    }

    public function doMobileFansList()
    {
        global $_GPC, $_W;
        $keyword = $_GPC['keyword'];
        $result  = [];
        //如果没有输入关键字默认@管理员列表
        if(empty($keyword)) {
            $result = pdo_fetchall("SELECT `nickname`,`headimgurl`,`openid`,`state`  FROM " . tablename($this->fanstable) . " WHERE  `state` = 2 AND `uniacid` = {$_W['uniacid']}");
        } else {
            $result = pdo_fetchall("SELECT `nickname`,`headimgurl`,`openid`,`state`  FROM " . tablename($this->fanstable) . " WHERE `uniacid` = {$_W['uniacid']}  AND  `nickname` LIKE '%{$keyword}%'");
        }

        if(!empty($result)) {
            $result = ["errcode" => 0, "errmsg" => "success", "data" => $result];
        } else {
            $result = ["errcode" => 1, "errmsg" => "fail", "data" => 0];
        }

        $this->renderJs($result);
    }
}