<?php
namespace Home\Api;

use Think\Controller;

class OnlineBonusApi extends BEventApi
{

    //获取在线时间
    public function getInfo($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //如果带有时间
        if ($_POST['second'] > 0) {
            if (false === D('TDailyOnlineBonus')->setOnlineTime($this->mTid, $_POST['second'])) {//设置在线时间
                return false;
            }
        } else {
            if (false === D('TDailyOnlineBonus')->setCache2Online($this->mTid)) {//设置在线时间
                return false;
            }
        }

        //查询数据
        $field = array('last_receive_bonus', 'second');
        $row = D('TDailyOnlineBonus')->getRow($this->mTid, $field);
        if (empty($row)) {
            $row['last_receive_bonus'] = 0;
            $row['second'] = 0;
        }
        return $row;
    }

    //设置时间
    public function setTime()
    {
        if (false === D('TDailyOnlineBonus')->setCacheTime($this->mTid, $_POST['second'])) {//设置在线时间
            return false;
        } else {
            return true;
        }
    }

    //领取奖励
    public function receive()
    {

        //获取当前情况
        $row = D('TDailyOnlineBonus')->getRow($this->mTid);

        //查询是否已经领取
        if (!empty($row) && $row['last_receive_bonus'] >= $_POST['bonus_id']) {
            C('G_ERROR', 'online_bonus_received');
            return false;
        }

        //获取奖励配置
        $bonusConfig = D('Static')->access('online_bonus');

        //简单验证在线时间是否满足
        $now = time();//当前时间
        $need = $_POST['bonus_id'] * 60;//在线时间
        if (empty($row)) {
            $overTime = $now - get_daily_utime();//计算时间
            $config = current($bonusConfig);
            if ($config['index'] != $_POST['bonus_id']) {
                C('G_ERROR', 'login_timeout');
                return false;
            }
            $secondAll = $_POST['second'];//本次奖励总上线时间
            $isRow = false;//是否存在数据
        } else {
            $overTime = $now - $row['last_receive_time'];
            $secondAll = $_POST['second'] + $row['second'];
            $isRow = true;
        }

        //验证两次领取时间是否有足够间隔
        if ($overTime < $need) {
            C('G_ERROR', 'login_timeout');
            return false;
        }

        if ($secondAll < $need) {
            D('TDailyOnlineBonus')->setOnlineTime($this->mTid, $_POST['second'], $isRow);//设置在线时间
            C('G_ERROR', 'online_bonus_not_come');
            return false;
        }

        //开始事务
        $this->transBegin();

        //奖励
        if (!$this->bonus($bonusConfig[$_POST['bonus_id']])) {
            goto end;
        }

        //记录
        if (!D('TDailyOnlineBonus')->receive($this->mTid, $_POST['bonus_id'], $row)) {
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