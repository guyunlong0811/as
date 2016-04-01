<?php
namespace Home\Api;

use Think\Controller;

class VipApi extends BaseApi
{

    //获取VIP奖励领取情况
    public function getBonusList($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        //返回
        return D('GVipBonus')->getList($this->mTid);
    }

    //领取VIP奖励
    public function receive()
    {
        //查询是否已经领取
        if (D('GVipBonus')->isReceived($this->mTid, $_POST['vip_id'])) {
            C('G_ERROR', 'vip_bonus_received');
            return false;
        }

        //获取当前VIP
        $vipIndex = D('GVip')->getAttr($this->mTid, 'index');
        if ($vipIndex < $_POST['vip_id']) {
            C('G_ERROR', 'vip_level_low');
            return false;
        }

        //获取配置
        $vipConfig = D('Static')->access('vip', $_POST['vip_id']);

        //开始事务
        $this->transBegin();

        //奖励
        if (!$this->bonus($vipConfig)) {
            goto end;
        }

        //记录
        if (!D('GVipBonus')->receive($this->mTid, $_POST['vip_id'])) {
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