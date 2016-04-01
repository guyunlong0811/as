<?php
namespace Home\Api;

use Think\Controller;

class ValityGrantApi extends BEventApi
{

    const GROUP = 10;

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) {
            exit;
        }
        return;
    }

    //返回活动次数
    public function getInfo()
    {
        return $this->mEventRemainCount;
    }

    //奖励
    public function receive()
    {

        if ($this->mEventRemainCount < 1) {
            C('G_ERROR', 'vality_grant_received_today');
            return false;
        }

        //开始事务
        $this->transBegin();

        //发放体力
        $vality = D('Static')->access('params', 'VALITY_COUNT');
        if (!$this->produce('vality', $vality)) {
            goto end;
        }

        //记录使用
        if (!$this->useEventCount()) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

}