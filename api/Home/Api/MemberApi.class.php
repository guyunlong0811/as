<?php
namespace Home\Api;

use Think\Controller;

class MemberApi extends BaseApi
{

    //获取会员到期时间
    public function getList($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        $now = time();
        $utime = get_daily_utime();
        //获取配置
        $memberConfig = D('Static')->access('member');
        //获取玩家数据
        $memberList = D('GMember')->getAll($this->mTid);
        $list = array();
        foreach ($memberConfig as $value) {
            $arr['id'] = $value['index'];
            if (isset($memberList[$value['index']])) {
                if ($value['gain_period'] < 1440) {
                    $cd = $memberList[$value['index']]['last_time'] + ($value['gain_period'] * 60) - $now;
                } else {
                    if ($memberList[$value['index']]['last_time'] >= $utime) {
                        $cd = $utime + 86400 - $now;
                    } else {
                        $cd = 0;
                    }
                }
                $cd = $cd < 0 ? 0 : $cd;
                $arr['cd'] = $cd;
                $arr['dtime'] = $memberList[$value['index']]['dtime'];
            } else {
                $arr['cd'] = 0;
                $arr['dtime'] = 0;
            }
            if ($value['gain_type'] == '1') {
                $arr['cd'] = -1;
            }

            $list[] = $arr;
        }
        return $list;
    }

    //领取奖励
    public function receive()
    {
        //获取配置
        $memberConfig = D('Static')->access('member', $_POST['member_id']);
        //会员卡是否是周期奖励
        if ($memberConfig['gain_type'] == '1') {
            C('G_ERROR', 'not_period_member');
            return false;
        }
        //查询会员情况
        $row = D('GMember')->getRow($this->mTid, $_POST['member_id'], array('dtime', 'last_time',));
        //会员是否到期
        $now = time();
        if ($row['dtime'] < $now) {
            C('G_ERROR', 'member_expire');
            return false;
        }
        //本时段是否已经领取过
        if ($memberConfig['gain_period'] < 1440) {//领取周期小于1天
            if ($row['last_time'] + ($memberConfig['gain_period'] * 60) > $now) {
                C('G_ERROR', 'member_bonus_received_already');
                return false;
            }
        } else if ($memberConfig['gain_period'] == 1440) {
            if ($row['last_time'] > get_daily_utime()) {
                C('G_ERROR', 'member_bonus_received_already');
                return false;
            }
        }

        //开始事务
        $this->transBegin();

        //奖励
        if (!$this->bonus($memberConfig)) {
            goto end;
        }

        //记录
        if (false === D('GMember')->receive($this->mTid, $_POST['member_id'])) {
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