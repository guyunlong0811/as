<?php
namespace Home\Api;

use Think\Controller;

class ActivityApi extends BaseApi
{

    //获取成就情况
    public function getInfo($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        //查询累计登录天数
        $info['activity'] = $this->schedule('dailyAttr', 7001);
        $where['tid'] = $this->mTid;
        $info['received'] = D('TDailyActivityBonus')->where($where)->getField('bonus', true);
        $info['received'] = empty($info['received']) ? array() : $info['received'];
        return $info;
    }

    //领取奖励
    public function receive()
    {

        //检查活跃度是否足够
        if (!$this->verify($_POST['bonus_id'], 'dailyAttr', 7001)) {
            return false;
        }

        //查询是否已经领取奖励
        if (D('TDailyActivityBonus')->isReceived($this->mTid, $_POST['bonus_id'])) {
            C('G_ERROR', 'activity_bonus_received');
            return false;
        }

        //获取配置
        $config = D('Static')->access('activity_bonus', $_POST['bonus_id']);

        //开始事务
        $this->transBegin();

        //记录领取
        if (false === D('TDailyActivityBonus')->receive($this->mTid, $_POST['bonus_id'])) {
            goto end;
        }

        //获得奖励
        if (false === $this->bonus($config)) {
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