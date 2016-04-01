<?php
namespace Home\Api;

use Think\Controller;

class LoginContinuousApi extends BaseApi
{

    const TYPE = 8;

    //返回现在是否可领
    public function getInfo()
    {
        //最大登录奖励天数
        $config = D('Static')->access('login_continuous');
        $config = end($config);
        $max = $config['index'];

        //查询连续登录天数
        $return['login'] = D('GTeam')->getAttr($this->mTid, 'login_continuous');
        $return['login'] = $return['login'] > $max ? $max : $return['login'];
        
        //查询领取奖励记录
        $return['count'] = D('TDailyCount')->getCount($this->mTid, self::TYPE);
        return $return;
    }


    //领取奖励
    public function receive()
    {

        $info = $this->getInfo();

        //查询是否有登录记录
        if ($info['count'] > 0) {
            C('G_ERROR', 'login_continuous_bonus_received');
            return false;
        }

        //查询奖品
        $config = D('Static')->access('login_continuous');
        if (empty($config[$info['login']])) {
            $config = end($config);
        } else {
            $config = $config[$info['login']];
        }

        //开始事务
        $this->transBegin();

        //修改领取标记
        if (false === D('TDailyCount')->record($this->mTid, self::TYPE)) {
            goto end;
        }

        //获得奖励
        if (!$this->bonus($config)) {
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