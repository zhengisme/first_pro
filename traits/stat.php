<?php

trait stat
{

    protected function topicStat($topicId = 0, $sectionId = 0)
    {
        $table   = tablename($this->topicstattable);
        $uniacid = $this->conf->uniacid;
        if(0 == $topicId && 0 == $sectionId) {
            return medoo()->query("
                insert into {$table} (uniacid, topicid, sectionid) value ('{$uniacid}', 0, 0)
                on duplicate key update count = count + 1
            ");
        }
        medoo()->query("
            insert into {$table} (uniacid, topicid, sectionid) value ('{$uniacid}', '{$topicId}', {$sectionId})
            on duplicate key update count = count + 1
        ");
    }

    protected function stat($topicId = 0, $sectionId = 0)
    {
        $data = medoo()->get($this->topicstattable, ['topicid', 'sectionid', 'count',], [
            'AND' => [
                'uniacid'   => $this->conf->uniacid,
                'topicid'   => $topicId,
                'sectionid' => $sectionId,
            ],
        ]) ?: [];
        if(0 == $topicId && 0 == $sectionId) {
            $member     = medoo()->count($this->fanstable, ['uniacid' => $this->conf->uniacid,]);
            $topicCount = medoo()->count($this->topictable, ['uniacid' => $this->conf->uniacid,]);
            return ['pv' => $data['count'], 'member' => $member, 'topic_count' => $topicCount,];
        }

        $followers = medoo()->count($this->topicfollowedtable, [
            'AND' => [
                'uniacid' => $this->conf->uniacid,
                'topicid' => $topicId,
                'guest'   => [0, 1],
            ],
        ]);
        return array_merge($data, ['followers' => $followers,]);
    }

    protected function topicListstat($topicIds)
    {
        $topicIds = implode(',', $topicIds);

        $sctionSql  = "SELECT topicid,count(id) as sectionsum FROM " . tablename($this->sectiontable) . " WHERE uniacid = '{$this->conf->uniacid}'  and state = 2  and topicid in ({$topicIds}) GROUP BY topicid";
        $sectionsum = pdo_fetchall($sctionSql) ?: [];
        $sectionsum = array_column($sectionsum, 'sectionsum', 'topicid');
        return ['sectioncounts' => $sectionsum, 'followcounts' => 0];
    }


    public function doWebStat()
    {
        global $_W, $_GPC, $_GET;
        $startdate         = $_GPC['time']['start'] ? $_GPC['time']['start'] : 0;
        $enddate           = $_GPC['time']['end'] ? $_GPC['time']['end'] : 0;
        $_GET['startdate'] = $startdate;
        $_GET['enddate']   = $enddate;
        $starttime         = strtotime($startdate);
        $endtime           = strtotime($enddate);
        $enddate           = date('Y-m-d', $endtime + 86400);
        $totaldata         = $this->getTotalStat();
        $datedata          = $this->getStatByDate($startdate, $enddate);

        $excelurl = $_W['script_name'] . "?" . http_build_query($_GET);
        if(preg_match("/eid=\w+/", $excelurl, $match)) {
            $excelurl = preg_replace("/eid=\w+/", "do=stattoexcel&m=ttq_ylbg", $excelurl);
        }
        include $this->template('operastat');
    }


    public function doWebStatToExcel()
    {
        global $_W, $_GPC, $_GET;
        $startdate = $_GET['startdate'] ? $_GET['startdate'] : ($_GPC['time']['start'] ? $_GPC['time']['start'] : 0);
        $enddate   = $_GET['enddate'] ? $_GET['enddate'] : ($_GPC['time']['end'] ? $_GPC['time']['end'] : 0);
        $enddate   = date('Y-m-d', strtotime($enddate) + 86400);
        $datedata  = $this->getStatByDate($startdate, $enddate);
        $filename  = ($startdate && $enddate) ? $startdate . "-" . $enddate : '每日新增';
        $this->toExcel($datedata, ['日期', '新增帖子数', '新增用户数'], $filename);
    }

    protected function getTotalStat()
    {
        global $_W;

        $y            = date('Y');
        $m            = date('m');
        $d            = date('d');
        $today        = date("Y-m-d H:i:s", mktime(0, 0, 0, $m, $d, $y));
        $thismonth    = date("Y-m-d H:i:s", mktime(0, 0, 0, $m, 1, $y));
        $thisweek     = date("Y-m-d H:i:s", mktime(0, 0, 0, $m, $d, $y) - date('w') * 86400);
        $sectiontable = tablename($this->sectiontable);
        $fanstable    = tablename($this->fanstable);
        $sql_total    = "select count(*) as count from ";
        $sql_section  = "SELECT COUNT(*) as count FROM {$sectiontable} WHERE `addtime`>:time AND `uniacid`={$_W['uniacid']}";
        $sql_fans     = "SELECT COUNT(*) as count FROM {$fanstable} WHERE `addtime`>:time AND `uniacid`={$_W['uniacid']}";

        $data                        = [];
        $data['totalsectionnum']     = pdo_fetch($sql_total . $sectiontable . " WHERE `uniacid`={$_W['uniacid']}");
        $data['todaysectionnum']     = pdo_fetch($sql_section, [":time" => $today]);
        $data['thismonthsectionnum'] = pdo_fetch($sql_section, [":time" => $thismonth]);
        $data['thisweeksectionnum']  = pdo_fetch($sql_section, [":time" => $thisweek]);
        $data['totalfansnum']        = pdo_fetch($sql_total . $fanstable . " WHERE `uniacid`={$_W['uniacid']}");
        $data['todayfansnum']        = pdo_fetch($sql_fans, [":time" => $today]);
        $data['thismonthfansnum']    = pdo_fetch($sql_fans, [":time" => $thismonth]);
        $data['thisweekfansnum']     = pdo_fetch($sql_fans, [":time" => $thisweek]);
        return $data;
    }


    public function getStatByDate($startdate, $enddate)
    {
        global $_W;
        //日期初始化
        $y = date('Y');
        $m = date('m');
        $d = date('d');

        if(empty($startdate) || empty($enddate)) {
            $enddate   = date("Y-m-d H:i:s", mktime(0, 0, 0, $m, $d, $y) + 86400);
            $startdate = date("Y-m-d H:i:s", mktime(0, 0, 0, $m, $d, $y) - 6 * 86400);
        }
        //帖子表与粉丝表
        $sectiontable = tablename($this->sectiontable);
        $fanstable    = tablename($this->fanstable);
        //sql语句
        $sql_section = "SELECT * FROM {$sectiontable} WHERE `addtime`>:startdate AND addtime<:enddate AND `uniacid`={$_W['uniacid']} ORDER BY `addtime` DESC";
        $sql_fans    = "SELECT * FROM {$fanstable} WHERE `addtime`>:startdate AND addtime<:enddate AND `uniacid`={$_W['uniacid']} ORDER  BY `addtime` DESC";
        //得到原始数据记录
        $sectiondata = pdo_fetchall($sql_section, [":startdate" => $startdate, ":enddate" => $enddate]);
        $fansdata    = pdo_fetchall($sql_fans, [":startdate" => $startdate, ":enddate" => $enddate]);
        //按日期整合
        $sectiondata = $this->getSectionDataArr($sectiondata);
        $fansdata    = $this->getFansDataArr($fansdata);
        //合并帖子和用户的数据
        $data = $this->mergeData($sectiondata, $fansdata);
        return $data;
    }

    /**合并帖子和用户的数据
     *
     */
    public function mergeData($sectiondata, $fansdata)
    {
        $sectiondatakeys = array_keys($sectiondata);
        $fansdatakeys    = array_keys($fansdata);
        $datearr         = array_unique(array_merge($sectiondatakeys, $fansdatakeys));//取日子
        $this->datesort($datearr);
        $sevendaysData = [];
        $tmp           = ['date' => '', 'sectionnum' => 0, 'fansnum' => 0];
        //根据日子存进数组
        foreach($datearr as $date) {
            $tmp['date']       = $date;
            $tmp['sectionnum'] = $sectiondata[$date] ? $sectiondata[$date]['sectionnum'] : 0;
            $tmp['fansnum']    = $fansdata[$date] ? $fansdata[$date]['fansnum'] : 0;
            $sevendaysData[]   = $tmp;
        }
        return $sevendaysData;
    }

    /**
     * 数组排序
     */
    private function datesort(&$datearr)
    {
        function comp($a, $b)
        {
            if($a == $b) {
                return 0; // 返回0
            }
            return (strtotime($a) < strtotime($b)) ? 1 : -1;
        }

        usort($datearr, "comp");
    }

    /**帖子数据整理
     * @param unknown $sevendays_list
     */
    private function getSectionDataArr($sevendays_list)
    {
        //数据相关
        $sevendaysData = [];
        $tmp           = ['date' => '', 'sectionnum' => 0, 'fansnum' => 0];
        foreach($sevendays_list as $val) {
            $date = date('Y-m-d', strtotime($val['addtime']));
            if($date == $tmp['date']) {
                $tmp['sectionnum'] += 1;
            } else {
                if($tmp['date'])
                    $sevendaysData[$tmp['date']] = $tmp;
                $tmp['date']       = $date;
                $tmp['sectionnum'] = 1;
            }
        }
        if($tmp['date']) {
            $sevendaysData[$tmp['date']] = $tmp;
        }
        return $sevendaysData;
    }


    /**用户数据整理
     * @param unknown $sevendays_list
     */
    private function getFansDataArr($sevendays_list)
    {
        //数据相关
        $sevendaysData = [];
        $tmp           = ['date' => '', 'sectionnum' => 0, 'fansnum' => 0];
        foreach($sevendays_list as $val) {
            $date = date('Y-m-d', strtotime($val['addtime']));
            if($date == $tmp['date']) {
                $tmp['fansnum'] += 1;
            } else {
                if($tmp['date'])
                    $sevendaysData[$tmp['date']] = $tmp;
                $tmp['date']    = $date;
                $tmp['fansnum'] = 1;
            }
        }
        if($tmp['date']) {
            $sevendaysData[$tmp['date']] = $tmp;
        }
        return $sevendaysData;
    }

    /**导出到excel
     * @param data为数据
     * @param th为表头array
     * @param $filename为文件名
     */
    public function toExcel($data, $th, $filename)
    {
        ///使用自带phpexcel
        $file = IA_ROOT . '/framework/library/phpexcel/PHPExcel.php';
        if(file_exists($file)) {
            include $file;
        } else {
            die("系统文件缺失");
        }

        $excel = new PHPExcel();
        //Excel表格式,这里简略写了3列
        //取th元素数量
        $abc = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $c   = count($th);
        //生成letter
        $letter = [];
        for($i = 0; $i < $c; $i++) {
            $letter[] = $abc[$i];
        }
        //表头数组
        $tableheader = $th;
        //填充表头信息
        for($i = 0; $i < count($tableheader); $i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }
        //表格数组

        //填充表格信息
        for($i = 2; $i <= count($data) + 1; $i++) {
            $j = 0;
            foreach($data[$i - 2] as $key => $value) {
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i", "$value");
                $j++;
            }
        }
        //输出
        $write = new PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename=' . $filename . '.xls');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }


}
