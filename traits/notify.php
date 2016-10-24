<?php

trait notify
{
    protected function setWechatNotifyTpl($head, $data, $color = '#173177')
    {
        return true;
    }

    public function sendNoticeAudit($url, $fansname, $content, $topicId, $openids = [])
    {
        return true;

    }

    public function sendZwqNotice($url, $fansname, $content, $sectionid, $openids = [])
    {
        return true;

    }

    public function sendNotice($sectionid, $toopenid, $nickname, $content, $toppicTitle = null)
    {
        return true;

    }

    public function sendPostNotice($sectionid, $toopenid, $nickname, $content, $toppicTitle = null)
    {
        return true;

    }

    public function sendNoticeTixian($toopenid, $fee)
    {
        return true;
    }

    protected function wechatNotify($body)
    {
        return true;
    }

}