<?php

/*
	共用工具类
*/

class Util
{
    public static $temppath = '../addons/zb_task/public';

    public static $staticpubParam = [
        'taskpurpose'  => ['壮梢试验', '溃疡病试验', '红蜘蛛试验',],
        'taskvariety'  => ['砂糖橘', '蜜柚', '沙田柚', '脐橙', '贡柑', '沃柑', '茂谷柑', '不知火', '马水橘', '春甜橘', '温州蜜柑', '南丰蜜桔', '冰糖橙', '茶枝柑', '潮州柑', '春见', '爱媛', '红江橙',],
        'taskstage'    => ['清园期', '春梢期', '幼果期', '秋梢期', '膨果期', '转色期', '贮藏期'],
        'taskexname'   => ['爽到底','绿琦'],
        'taskway'      => ['喷雾', '淋施', '涂抹',],
        'taskcycle'    => 0,
        'taskmultiple' => 0,
        'otherdesc'    => 0,
    ];
    public static $taskcoverarr  = [
        '炭疽病'=>['num'=>1,'index'=>1,'state'=>1],
        '褐斑病'=>['num'=>1,'index'=>2,'state'=>1],
        '溃疡病'=>['num'=>1,'index'=>3,'state'=>1],
        '疮痂病'=>['num'=>1,'index'=>4,'state'=>0],
        '砂皮病'=>['num'=>1,'index'=>5,'state'=>1],
        '黄化'=>['num'=>1,'index'=>6,'state'=>0],
        '黄龙病'=>['num'=>1,'index'=>7,'state'=>0],
        '青苔'=>['num'=>1,'index'=>8,'state'=>1],
        '褐腐病'=>['num'=>1,'index'=>9,'state'=>1],
        '裙腐病'=>['num'=>1,'index'=>10,'state'=>1],
        '流胶病'=>['num'=>1,'index'=>11,'state'=>1],
        '煤烟病'=>['num'=>1,'index'=>12,'state'=>0],
        '脂点黄斑病'=>['num'=>1,'index'=>13,'state'=>1],
        '红蜘蛛'=>['num'=>3,'index'=>14,'state'=>1],
        '潜叶蛾'=>['num'=>1,'index'=>15,'state'=>1],
        '蓟马'=>['num'=>1,'index'=>16,'state'=>1],
        '锈蜘蛛'=>['num'=>1,'index'=>17,'state'=>1],
        '木虱'=>['num'=>1,'index'=>18,'state'=>0],
        '蚧壳虫'=>['num'=>1,'index'=>19,'state'=>1],
        '天牛'=>['num'=>1,'index'=>20,'state'=>1],
        '线虫'=>['num'=>1,'index'=>21,'state'=>0],
        '蜗牛'=>['num'=>1,'index'=>22,'state'=>1],
        '尺蠖'=>['num'=>1,'index'=>23,'state'=>0],
        '黑头虫'=>['num'=>1,'index'=>24,'state'=>0],
        '蚜虫'=>['num'=>1,'index'=>25,'state'=>0],
        '实蝇'=>['num'=>1,'index'=>26,'state'=>1],
        '小实蝇'=>['num'=>1,'index'=>27,'state'=>0],
        '粉虱'=>['num'=>1,'index'=>28,'state'=>0],
        '蚱蝉'=>['num'=>1,'index'=>29,'state'=>0],
        '叶甲'=>['num'=>1,'index'=>30,'state'=>1],
        '卷叶虫'=>['num'=>1,'index'=>31,'state'=>1],
        '出梢'=>['num'=>1,'index'=>32,'state'=>0],
        '壮梢'=>['num'=>1,'index'=>33,'state'=>0],
        '控梢'=>['num'=>1,'index'=>34,'state'=>0],
        '促花'=>['num'=>1,'index'=>35,'state'=>0],
        '壮花'=>['num'=>1,'index'=>36,'state'=>0],
        '保果'=>['num'=>1,'index'=>37,'state'=>0],
        '膨果'=>['num'=>1,'index'=>38,'state'=>0],
        '转色'=>['num'=>1,'index'=>39,'state'=>0],
        '保鲜'=>['num'=>1,'index'=>40,'state'=>0],
        '防冻'=>['num'=>1,'index'=>41,'state'=>0],
        '清园'=>['num'=>1,'index'=>42,'state'=>0],
        '防裂果'=>['num'=>1,'index'=>43,'state'=>0],
        '防日灼'=>['num'=>1,'index'=>44,'state'=>0],
        '缺铜'=>['num'=>1,'index'=>45,'state'=>1],
        '缺铁'=>['num'=>1,'index'=>46,'state'=>1],
        '缺硼'=>['num'=>1,'index'=>47,'state'=>1],
        '缺镁'=>['num'=>1,'index'=>48,'state'=>1],
        '缺磷'=>['num'=>1,'index'=>49,'state'=>1],
        '油斑'=>['num'=>1,'index'=>50,'state'=>1],
        '斜纹夜蛾'=>['num'=>1,'index'=>51,'state'=>1],
        '花瓣果'=>['num'=>1,'index'=>52,'state'=>1],
        '凤蝶'=>['num'=>1,'index'=>53,'state'=>1],
        '潜叶跳甲'=>['num'=>1,'index'=>54,'state'=>1],
    ];

    public static $staticanswerParam = [
        [
            "paramname"  => 'cropage',
            "paramtype"  => 'text',
            "paramtitle" => '树龄',
            "hide"       => 1,
        ],
    ];

    //微信端上传图片 传入微信端下载的图片
    static function uploadImageInWeixin($resp)
    {
        global $_W;
        $setting           = $_W['setting']['upload']['image'];
        $setting['folder'] = "images/{$_W['uniacid']}" . '/' . date('Y/m/');

        /* 		load()->func('communication');
                $resp = ihttp_get($url); */
        load()->func('file');
        $result["errcode"] = 1;
        if(is_error($resp)) {
            $result['message'] = '提取文件失败, 错误信息: ' . $resp['message'];
            return json_encode($result);
        }
        if(intval($resp['code']) != 200) {
            $result['message'] = '提取文件失败: 未找到该资源文件.';
            return json_encode($result);
        }
        $ext = '';

        switch($resp['headers']['Content-Type']) {
            case 'application/x-jpg':
            case 'image/jpeg':
                $ext = '.jpg';
                break;
            case 'image/png':
                $ext = '.png';
                break;
            case 'image/gif':
                $ext = '.gif';
                break;
            default:
                $result['message'] = '提取资源失败, 资源文件类型错误.';
                return json_encode($result);
                break;
        }

        if(intval($resp['headers']['Content-Length']) > $setting['limit'] * 1024) {
            $result['message'] = '上传的媒体文件过大(' . sizecount($size) . ' > ' . sizecount($setting['limit'] * 1024);
            return json_encode($result);
        }

        $filename = date('YmdHis') . '_' . rand(1000000000, 9999999999.0) . '_' . rand(1000, 9999) . $ext;
        $wr       = file_write('/images/zb_super/' . $filename, $resp['content']);
        if($wr) {
            $file_succ[] = ['name' => $filename, 'path' => '/images/zb_super/' . $filename, 'spath' => 'images/zb_super/' . $filename, 'type' => 'local'];
        } else {
            $result['message'] = '提取失败.';
            return json_encode($result);
        }

        foreach($file_succ as $key => $value) {
            $r = file_remote_upload($value['spath']);
            @unlink(ATTACHMENT_ROOT . $value['path']);
            if(is_error($r)) {
                sleep(1);//重试第1次
                $r = file_remote_upload($value['spath']);
            }
            if(is_error($r)) $r = file_remote_upload($value['spath']);//重试第2次
            if(is_error($r)) {
                unset($file_succ[$key]);
                continue;
            }
            $file_succ[$key]['remotepath'] = tomedia($value['spath']);
            $file_succ[$key]['type']       = 'other';
        }

        $pathname = $file_succ[0]['remotepath'];
        if(empty($pathname)) {
            $url = $_W['attachurl'] . $file_succ[0]['path'];
        }

        return json_encode(["errcode" => 0, "result" => 1, "errmsg" => "success", "url" => $pathname, "nameval" => $file_succ[0]['path']]);

    }


    //删除所有缓存 每次设置参数后都要删除
    static function deleteThisModuleCache()
    {
        global $_W;
        $res = cache_clean('zb_task');
        return $res;
    }

    //组合URL
    static function taskModuleCreateUrl($do, $array)
    {
        global $_W;
        $str = '&do=' . $do;
        foreach($array as $k => $v) {
            $str .= '&' . $k . '=' . $v;
        }
        return $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry' . $str . '&m=zb_task';
    }

    //计算平台使用费
    static function countServer($moduel, $money)
    {
        return max($moduel->module['config']['servermoney'] * $money / 100, $moduel->module['config']['leastserver']);
    }

    //返回订阅uid字符串
    static function returnFocusStr($uid)
    {
        $focusinfo = model_focus::getAllMyFocus($uid);
        $focusstr  = ',';
        foreach((array)$focusinfo as $k => $v) {
            $focusstr .= $v . ',';
        }
        return $focusstr;
    }


    //组合数据查询where字符串 = ,>= ,<= <、>必须紧挨字符 例：$where = array('status'=>1,'overtime<'=>time());
    static function structWhereString($array, $head = '')
    {
        $str = "";
        foreach((array)$array as $k => $v) {
            if(strpos($k, '>') !== false) {
                $k = trim(trim($k), '>');
                $str .= empty($head) ? " AND " . $k . " >= '" . $v . "' " : " AND " . $head . '.' . $k . " >= '" . $v . "' ";
            } elseif(strpos($k, '<') !== false) {
                $k = trim(trim($k), '<');
                $str .= empty($head) ? " AND " . $k . " <= '" . $v . "' " : " AND " . $head . '.' . $k . " <= '" . $v . "' ";
            } elseif(strpos($k, '!') !== false) {
                $k = trim(trim($k), '!');
                $str .= empty($head) ? " AND " . $k . " != '" . $v . "' " : " AND " . $head . '.' . $k . " != '" . $v . "' ";
            } elseif(strpos($k, '@') !== false) {
                $k = trim(trim($k), '@');
                $str .= empty($head) ? " AND " . $k . " IN '" . $v . "' " : " AND " . $head . '.' . $k . " IN " . $v . " ";
            } else {

                $str .= empty($head) ? " AND " . $k . " = '" . $v . "' " : " AND " . $head . '.' . $k . " = '" . $v . "' ";

            }
        }
        return $str;
    }

    //更新单条数据，对数据进行加减
    static function addAndMinusData($tablename, $countarray, $wherearray)
    {
        global $_W;
        $count = '';
        foreach($countarray as $k => $v) {
            $count .= ' `' . $k . '`' . ' = ' . ' `' . $k . '` ' . ' + ' . $v . ',';
        }
        $count = trim($count, ',');

        $where = '';
        foreach((array)$wherearray as $k => $v) {
            if(isset($v)) $where .= " AND `" . $k . "` = '" . $v . "' ";
        }

        $res = pdo_query("UPDATE " . tablename($tablename) . " SET $count WHERE uniacid = '{$_W['uniacid']}' $where ");
        if($res) return true;
        return false;
    }

    //更新单条数据，没有相加功能
    static function updateSingleData($tablename, $updatearray, $wherearray)
    {
        global $_W;
        foreach((array)$updatearray as $k => $v) {
            if(isset($v)) $update .= "`" . $k . "` = '" . $v . "',";
        }
        $update = trim($update, ',');

        foreach((array)$wherearray as $k => $v) {
            if(isset($v)) $where .= " AND `" . $k . "` = '" . $v . "' ";
        }

        $res = pdo_query("UPDATE " . tablename($tablename) . " SET $update WHERE uniacid = '{$_W['uniacid']}' $where");
        if($res) return true;
        return false;
    }


    //查询单条数据
    static function getSingleData($tablename, $wherearray)
    {
        global $_W;
        $str      = Util::structWhereString($wherearray);
        $datainfo = pdo_fetch("SELECT * FROM " . tablename($tablename) . " WHERE uniacid ='{$_W['uniacid']}' $str");
        return $datainfo;
    }

    //批量查询 如果$k里加>,<，那么是范围查询。
    static function getAllData($tablename, $wherearray, $page = 1, $num = 10, $order = 'id')
    {
        global $_W;
        $pindex  = max(1, intval($page));
        $psize   = $num;
        $str     = self::structWhereString($wherearray);
        $total   = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename($tablename) . " WHERE `uniacid` ='{$_W['uniacid']}' $str ");
        $datanfo = pdo_fetchall("SELECT * FROM " . tablename($tablename) . " WHERE `uniacid` ='{$_W['uniacid']}' $str ORDER BY $order DESC " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
        $pager   = pagination($total, $pindex, $psize);
        return [$datanfo, $pager];
    }

    //计算满足条件的数据数量
    static function countData($tablename, $array)
    {
        global $_W;
        $str = '';
        foreach((array)$array as $k => $v) {
            $str .= ' AND `' . $k . '` = ' . $v . ' ';
        }
        $number = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename($tablename) . " WHERE `uniacid` ='{$_W['uniacid']}' $str");
        return $number;
    }


    //删除数据库
    static function deleteData($id, $tablename)
    {
        global $_W;
        $id       = intval($id);
        $datainfo = pdo_fetch("SELECT `id` FROM " . tablename($tablename) . " WHERE `uniacid` = '{$_W['uniacid']}' AND `id` = '{$id}'");
        if(empty($datainfo)) {
            message('抱歉，数据不存在或是已经被删除！');
        }
        $res = pdo_delete($tablename, ['id' => $id, 'uniacid' => $_W['uniacid']], 'AND');
        return $res;
    }

    //处理空格
    static function trimWithArray($array)
    {
        if(!is_array($array)) {
            return trim($array);
        }
        foreach($array as $k => $v) {
            $res[$k] = self::trimWithArray($v);
        }
        return $res;
    }

    //用户唯一session字符串
    static function sessionAndCookieUserStr($openid)
    {
        global $_W;
        if(empty($openid)) return false;
        $rump = substr($openid, -20);
        return 'fendauser' . $rump . $_W['uniacid'];
    }

    //获取cookie 传入cookie名 //解决js与php的编码不一致情况。
    static function getCookie($str)
    {
        return urldecode($_COOKIE[$str]);
    }

    //格式化时间,多久之前
    static function formatTime($time)
    {
        $difftime = time() - $time;
        if($difftime < 60) {
            return $difftime . '秒前';
        } elseif($difftime < 120) {
            return '1分钟前';
        } elseif($difftime < 3600) {
            return intval($difftime / 60) . '分钟前';
        } elseif($difftime < 3600 * 24) {
            return intval($difftime / 60 / 60) . '小时前';
        } elseif($difftime < 3600 * 24 * 2) {
            return '1天内';
        } elseif($difftime < 3600 * 24 * 30) {
            return intval($difftime / 60 / 60 / 24) . '天前';
        } elseif($difftime < 3600 * 24 * 365) {
            return intval($difftime / 60 / 60 / 24 / 30) . '月前';
        } elseif($difftime < 3600 * 24 * 365 * 10) {
            return intval($difftime / 60 / 60 / 24 / 365) . '年前';
        } else {
            return '很久以前';
        }
    }

    //剩余时间
    static function lastTime($time, $secondflag = true)
    {
        $diff = $time - time();
        if($diff <= 0) return 0;
        $day     = intval($diff / 24 / 3600);
        $hour    = intval(($diff % (24 * 3600)) / 3600);
        $minutes = intval(($diff % (24 * 3600)) % 3600 / 60);
        $second  = $diff % 60;
        if($secondflag) {
            return $day . '天' . $hour . '时' . $minutes . '分' . $second . '秒';
        } else {
            return $day . '天' . $hour . '时' . $minutes . '分';
        }
    }


    static function getCache($key, $name)
    {
        global $_W;
        if(empty($key) || empty($name)) return false;
        return cache_read('zb_task:' . $_W['uniacid'] . ':' . $key . ':' . $name);
    }

    static function setCache($key, $name, $value)
    {
        global $_W;
        if(empty($key) || empty($name)) return false;

        $res = cache_write('zb_task:' . $_W['uniacid'] . ':' . $key . ':' . $name, $value);
        return $res;
    }

    static function deleteCache($key, $name)
    {
        global $_W;
        if(empty($key) || empty($name)) return false;

        return cache_delete('zb_task:' . $_W['uniacid'] . ':' . $key . ':' . $name);
    }

    /*
        //获取缓存 name文件名，dir目录名
         static function getCache($name,$dir = '') {
            global $_W;
            $dir = zb_task . 'cache/' .$_W['uniacid'] . '/' . $dir;
            $name = MD5($name.$GLOBALS['_W']['config']['setting']['authkey']);
            $file = $dir . '/' . $name.'.php';
            if (!is_file($file)) {
                return array();
            }
            return iunserializer(file_get_contents($file));
        }

        //设置缓存 name文件名，data缓存数据，dir目录名
        static function setCache($name,$data,$dir = '') {

            global $_W;
            $dir = zb_task . 'cache/' .$_W['uniacid'] . '/' . $dir;
            self::mkdirs($dir);
            $name = MD5($name.$GLOBALS['_W']['config']['setting']['authkey']);
            if (!is_string($data)) {
                $data = iserializer($data);
            }
            $file = $dir . '/' . $name.'.php';

            return file_put_contents($file,$data);
        }

        //删除缓存 name文件名，dir目录名
        static function deleteCache($name, $dir = '') {
            global $_W;
            if(empty($name)){
                return false;
            }
            $name = MD5($name.$GLOBALS['_W']['config']['setting']['authkey']);
            $dir = zb_task . 'cache/' .$_W['uniacid'] . '/' . $dir;
            $file = $dir . '/' . $name.'.php';
            return @unlink($file);
        }
     */

    //创建目录
    static function mkdirs($path)
    {
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return is_dir($path);
    }

    //核查文件是否存在
    static function fileExists($name, $dir = '')
    {
        global $_W;
        if(empty($name)) {
            return false;
        }
        $name = md5($name . $GLOBALS['_W']['config']['setting']['authkey']);
        $dir  = zb_task . 'cache/' . $_W['uniacid'] . '/' . $dir;
        $file = $dir . '/' . $name . '.php';
        if(file_exists($file)) {
            return true;
        } else {
            return false;
        }
    }

    public static function httpRequest($url, $post = '', $headers = [], $forceIp = '', $timeout = 60, $options = [])
    {
        load()->func('communication');
        return ihttp_request($url, $post, $options, $timeout);
    }

    //get请求
    public static function httpGet($url, $forceIp = '', $timeout = 60)
    {
        $res = self::httpRequest($url, '', [], $forceIp, $timeout);
        if(!is_error($res)) {
            return $res['content'];
        }
        return $res;
    }

    //post请求
    public static function httpPost($url, $data, $forceIp = '', $timeout = 60)
    {
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $res     = self::httpRequest($url, $data, $headers, $forceIp, $timeout);
        if(!is_error($res)) {
            return $res['content'];
        }
        return $res;
    }

    /**
     * 创建APP端URL地址
     * @param $act
     * @param $row
     * @return string
     */
    public static function createMUrl($act, $row = [])
    {
        global $_W;
        $params = array_merge(['i' => $_W['uniacid'], 'do' => $act, 'm' => 'zb_task',"c"=>'entry',], $row);
        return $_W['siteroot'] . 'app/index.php?' . http_build_query($params);
    }

    public static function keyBy($row, $key)
    {
        return array_reduce($row, function($r, $v) use ($key) {
            $r[$v[$key]] = $v;
            return $r;
        }, []);
    }

    /**
     * 添加访问记录，点赞或者反对
     * @param $row
     */
    public static function taskStatAdd($data)
    {
        global $_W;
        $fans            = (object)$_W['fans'];
        $row             = (object)$data;
        $row->returnType = isset($row->returnType) ? $row->returnType : 0;
        $like            = isset($row->like) ? $row->like : 0;
        $addTime         = date('Y-m-d H:i:s', TIMESTAMP);
        $plus            = $like ? "pro = '{$like}'," : '';
        medoo()->query("insert into `ims_zb_task_like` 
            set openid = '{$fans->openid}', uniacid = '{$fans->uniacid}', objectid = '{$row->objectid}', sectionid = '{$row->sectionid}', uid = '{$fans->uid}', nickname = '{$fans->nickname}', headimgurl = '{$fans->headimgurl}', `type` = '{$row->type}', `pro` = '{$like}', addtime = '{$addTime}', updatetime = '{$addTime}'
            on duplicate key update pv = pv + 1, {$plus} updatetime = '{$addTime}'"
        );
        return self::taskStat($data, $row->returnType);
    }

    public static function taskStat($data, $returnType = 0)
    {
        //0,直播pv
        $row = (object)$data;

        if(0 == $returnType) {
            $pv = medoo()->sum('zb_task_like', 'pv', ['AND' => [
                'objectid' => $row->objectid, 'type' => $row->type,
            ]]);
            return ['code' => 0, 'pv' => $pv,];
        }

        //1,帖子pv
        if(1 == $returnType) {
            $pv = medoo()->sum('zb_task_like', 'pv', ['AND' => [
                'objectid' => $row->objectid, 'sectionid' => $row->sectionid, 'type' => $row->type,
            ]]);
            return ['code' => 0, 'pv' => $pv,];
        }

        //2,实验报告的点赞和反对
        $total = medoo()->select('zb_task_like', ['pro', 'count(*)(cnt)', 'sum(pv)(total_pv)'], [
            'AND'   => ['sectionid' => $row->sectionid, 'objectid' => $row->objectid,],
            'GROUP' => 'type, objectid, sectionid',
        ]);

        if(empty($total)) return ['code' => 0, 'like' => 0, 'dislike' => 0,];
        $total = self::keyBy($total, 'pro');
        return ['code' => 0, 'like' => (int)$total[1]['cnt'], 'dislike' => (int)$total[2]['cnt'],];
    }
}
