<?php
namespace Home\Api;

use Think\Controller;

class LevelBonusApi extends BaseApi
{

    //获取在线时间
    public function getList($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        return D('GLevelBonus')->getReceived($this->mTid);
    }

    //领取奖励
    public function receive()
    {

        //获取当前情况
        if (D('GLevelBonus')->isReceived($this->mTid, $_POST['bonus_id'])) {
            C('G_ERROR', 'level_bonus_received');
            return false;
        }

        //检查玩家当前等级是否足够
        if (!$this->verify($_POST['bonus_id'], 'level')) {
            return false;
        }

        //获取奖励配置
        $bonusConfig = D('Static')->access('level_bonus', $_POST['bonus_id']);
        if (empty($bonusConfig)) {
            return true;
        }

        //开始事务
        $this->transBegin();

        //奖励
        if (!$this->bonus($bonusConfig)) {
            goto end;
        }

        //记录
        if (!D('GLevelBonus')->receive($this->mTid, $_POST['bonus_id'])) {
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