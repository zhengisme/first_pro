<?php

/**
 * 微信消息提醒，
 * Class WxNotify
 * 角色为四种
 * 1，用户， 提醒奖金到账， 提醒收到新评论， 提醒关注内容更新，
 * 2，会员， 提醒审核被通过， 提醒奖金到账， 提醒收到新评论， 提醒关注内容更新， 提醒提交报告，
 * 3，厂家， 提醒审核被通过， 提醒奖金到账， 提醒收到新评论， 提醒关注内容更新， 提醒提交报告，
 * 4，运营， 提醒审核新任务， 提醒审核新会员， 提醒审核新厂家， 提醒审核实验报告， 提醒奖金到账， 提醒收到新评论， 提醒关注内容更新， 提醒
 */

require_once IA_ROOT . '/resque/WechatNotify/init.php';

class WxNotify
{
    protected $titles = [
        'a' => '有新的任务等待你审核',
        'b' => '有新的厂家申请等待你审核',
        'c' => '有新的实验报告等待你审核',
        'd' => '你好，你提交的任务已审核',
        'e' => '你好，你提交的实验报告已审',
        'f' => '你好，你的注册申请已审核',
        'g' => '你好，你的注册申请已审核',
        'h' => '你好，你提取的金额已到账',
        'i' => '你好，实验报告[%s]收到一条评论',
        'j' => '你好，你关注的[%s]在[%s]中发布了新内容',
        'k' => '你好，你领取的任务需要尽快提交实验报告',
    ];

    /**
     * 配置文件 token
     * WxNotify constructor.
     * @param $config
     * @param $token
     */
    public function __construct($config, $token)
    {
        $this->config = $config;
        $this->token  = $token;
        $this->time   = date('Y-m-d H:i');
    }

    /**
     * 模板消息内容组装
     * @param $head
     * @param $body
     * @param $color
     * @return array
     */
    protected function sendNotify($head, $body, $color = '#173177')
    {
        $data = array_map(function($v) use ($color) {
            //数组用于颜色自定义
            if(true == is_array($v)) {
                return ['value' => $v[0], 'color' => '#' . $v[1],];
            }
            //使用缺省颜色
            return ['value' => $v, 'color' => $color,];
        }, $body);

        $content = array_merge($head, ['data' => $data,]);
        return Resque::enqueue('WECHAT_TASK_NOTIFY', 'WechatNotify', ['token' => $this->token, 'data' => $content,], true);
    }

    /**
     * 获取消息的title
     * @param $label
     * @return mixed
     */
    protected function title($label)
    {
        return $this->titles[$label];
    }

    /**
     * 获取消息的头部
     * @param $openid
     * @param $label
     * @param string $url
     * @param string $color
     * @return array
     */
    protected function head($openid, $label, $url = '', $color = '#060709')
    {
        return ['touser' => $openid, 'template_id' => $this->config[$label . '_id'], 'url' => $url, 'topcolor' => $color,];
    }

    /**
     * 获取自定义尾部的描述
     * @param $label
     * @return mixed
     */
    protected function remark($label)
    {
        return $this->config["{$label}_remark"];
    }

    //审核新任务
    public function msga($data, $label = 'a')
    {
        $head = $this->head($data['openid'], $label, $data['url']);
        $body = [
            'first'    => $this->title($label),
            'keyword1' => $data['keyword1'],
            'keyword2' => $data['keyword2'],
            'keyword3' => isset($data['keyword3']) ? $data['keyword3'] : $this->time,
            'remark'   => $this->remark($label),
        ];
        return $this->sendNotify($head, $body);
    }

    //审核新厂家
    public function msgb($data)
    {
        return $this->msga($data, 'b');
    }

    //审核实验报告
    public function msgc($data)
    {
        return $this->msga($data, 'c');
    }

    //任务审核通过
    public function msgd($data, $label = 'd')
    {
        $head = $this->head($data['openid'], $label, $data['url']);
        $body = [
            'first'    => $this->title($label),
            'keyword1' => $data['keyword1'],
            'keyword2' => $data['keyword2'],
            'remark'   => $this->remark($label),
        ];
        return $this->sendNotify($head, $body);
    }

    //实验报告审核完成提醒
    public function msge($data)
    {
        return $this->msgd($data, 'e');
    }

    //厂家注册成功提醒
    public function msgf($data)
    {
        return $this->msgd($data, 'f');
    }

    //会员注册成功提醒
    public function msgg($data)
    {
        return $this->msgd($data, 'g');
    }

    //奖金提现到账提醒
    public function msgh($data)
    {
        $head = $this->head($data['openid'], 'h', $data['url']);
        $body = [
            'first'  => $this->title('h'),
            'money'  => $data['money'],
            'timet'  => isset($data['timet']) ? $data['timet'] : $this->time,
            'remark' => $this->remark('h'),
        ];
        return $this->sendNotify($head, $body);
    }

    //提醒收到新评论
    public function msgi($data)
    {
        $head  = $this->head($data['openid'], 'i', $data['url']);
        $title = vsprintf($this->titles['i'], $data['report']);
        $body  = [
            'first'    => $title,
            'keyword1' => $data['keyword1'],
            'keyword2' => $data['keyword2'],
            'keyword3' => isset($data['keyword3']) ? $data['keyword3'] : $this->time,
            'remark'   => $this->remark('i'),
        ];
        return $this->sendNotify($head, $body);
    }

    //提醒关注内容更新
    public function msgj($data)
    {
        $head   = $this->head($data['openid'], 'j', $data['url']);
        $title = vsprintf($this->titles['j'], [$data['name'], $data['report']]);
        $body   = [
            'first'    => $title,
            'keyword1' => $data['keyword1'],
            'keyword2' => isset($data['keyword2']) ? $data['keyword2'] : $this->time,
            'remark'   => $this->remark('i'),
        ];
        return $this->sendNotify($head, $body);
    }

    //提醒提交报告
    public function msgk($data)
    {
        $head = $this->head($data['openid'], 'k', $data['url']);
        $body = [
            'first'    => $this->title('k'),
            'keyword1' => $data['keyword1'],
            'keyword2' => isset($data['keyword2']) ? $data['keyword2'] : $this->time,
            'remark'   => $this->remark('k'),
        ];
        return $this->sendNotify($head, $body);
    }
}
