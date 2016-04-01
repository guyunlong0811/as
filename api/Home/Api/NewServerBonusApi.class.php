<?php
namespace Home\Api;

use Think\Controller;

class NewServerBonusApi extends BaseApi
{

    const TYPE = 9;

    //返回现在是否可领
    public function getInfo()
    {
        //当前时间
        $now = time();

        //获取礼包截止时间
        $end = D('GParams')->getValue('NEW_SERVER_BONUS');
        $end = strtotime($end);

        if ($now <= $end) {
            $info['status'] = 1;
            $info['count'] = D('TDailyCount')->getCount($this->mTid, self::TYPE);
        } else {
            $info['status'] = 0;
            $info['count'] = 0;
        }

        //返回
        return $info;
    }

    //奖励
    public function receive()
    {
        //获取信息
        $info = $this->getInfo();

        //判断活动是否开放
        if ($info['status'] == 0) {
            C('G_ERROR', 'event_not_exist');
            return false;
        }

        //判断今天是否已经领取过
        if ($info['count'] > 0) {
            C('G_ERROR', 'event_not_exist');
            return false;
        }


        //开始事务
        $this->transBegin();

        //修改领取标记
        if (false === D('TDailyCount')->record($this->mTid, self::TYPE)) {
            goto end;
        }

        //获得奖励
        $boxId = D('Static')->access('params', 'START_WELFARE_BOX');
        if (false === $itemList = $this->produce('box', $boxId, 1)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return $itemList;

    }

}