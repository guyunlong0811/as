<?php
namespace Home\Api;

use Think\Controller;

class DailyRegisterApi extends BEventApi
{

    const GROUP = 9;

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) {
            exit;
        }
    }

    //获取背包列表
    public function getList()
    {

        //获取今天的更新时间
        $utime = get_daily_utime();
        $month = date('n', $utime);//月份

        //获取本月的签到配置信息
        $config = D('Static')->access('daily_register', $month);

        //获取本月的签到记录
        $dailyRegisterList = D('GDailyRegister')->getAll($this->mTid);
        //奖励状态1(1:已经单倍领取;2:已经双倍领取;)
        //奖励状态2(0:不可领取;1:可以免费补领;)
        $list = array();
        foreach ($config as $key => $value) {
            $arr['day'] = $value['day'];
            //查看有没有领取记录
            if (isset($dailyRegisterList[$value['day']])) {
                $arr['is_today'] = $utime <= $dailyRegisterList[$value['day']]['ctime'] ? 1 : 0;
                $arr['status1'] = $dailyRegisterList[$value['day']]['status'];
            } else {
                break;
            }

            if ($arr['status1'] == '2') {//如果当天已经双倍领取，则标识不能领取
                $arr['status2'] = '0';
            } else {//如果当天领取了单倍
                if ($value['min_vip_level'] == '0') {//如果当天没有双倍，则标识不能领取
                    $arr['status2'] = '0';
                } else {
                    $vipLevel = $this->schedule('vip');
                    if ($vipLevel >= $value['min_vip_level'] && $dailyRegisterList[$value['day']]['ctime'] >= $utime) {//如果vip等级够了，则标识可以领取
                        $arr['status2'] = '1';
                    } else {//如果vip等级不够，则标识不能领取
                        $arr['status2'] = '0';
                    }
                }
            }
            $list[] = $arr;
        }

        //返回
        $return['month'] = $month;
        $return['today'] = D('GDailyRegister')->isReceived($this->mTid) ? '1' : '0';//今天是否已经领取过
        $return['pay'] = D('GDailyRegister')->getPayCount($this->mTid);
        $return['list'] = $list;
        return $return;
    }

    //免费领取奖励
    public function receive()
    {

        //查看今天是否已经领取过
        $isGet = D('GDailyRegister')->isReceived($this->mTid);
        if ($isGet) {
            C('G_ERROR', 'event_bonus_received');
            return false;
        }

        //获取本月最后一天天数


        //获取今天的更新时间
        $utime = get_daily_utime();
        $month = date('n', $utime);//月份
        $day = D('GDailyRegister')->getCount($this->mTid);//查看玩家领取了几天
        ++$day;
        if($day > date('t')){
            C('G_ERROR', 'daily_register_complete');
            return false;
        }

        //查询当日奖励情况
        $config = D('Static')->access('daily_register', $month, $day);
        if (empty($config)) {
            C('G_ERROR', 'activity_bonus_end_this_month');
            return false;
        }

        //未领取
        if ($config['min_vip_level'] == 0) {
            $times = 1;//当日没有双倍
        } else {
            if (!$vipLevel = $this->verify($config['min_vip_level'], 'vip')) {
                $times = 1;//VIP不够则领单倍
            } else {
                $times = 2;//达到VIP等级领取双倍
            }
        }

        //开始事务
        $this->transBegin();

        //插入领取数据
        $data['tid'] = $this->mTid;
        $data['day'] = $day;
        $data['pay'] = 0;
        $data['status'] = $times;
        if (!D('GDailyRegister')->CreateData($data)) {
            goto end;
        }

        //发放奖励
        if (!$this->produce($this->mBonusType[$config['bonus']], $config['bonus_value_1'], $config['bonus_value_2'] * $times)) {
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

    //补领双倍奖励
    public function receiveDouble()
    {

        $utime = get_daily_utime();

        //查询奖励情况
        $row = D('GDailyRegister')->getRow($this->mTid, $_POST['day']);
        if (empty($row)) {
            C('G_ERROR', 'activity_bonus_not_received');
            return false;
        }

        //补领时间已经过期
        if ($row['ctime'] < $utime) {
            C('G_ERROR', 'daily_register_double_over');
            return false;
        }

        //已领取
        if ($row['status'] == '2') {
            C('G_ERROR', 'event_bonus_received');
            return false;
        }

        //当日没有双倍
        $month = date('n', $utime);//月份
        $config = D('Static')->access('daily_register', $month, $_POST['day']);
        if ($config['min_vip_level'] == 0) {
            C('G_ERROR', 'event_bonus_received');
            return false;
        }

        //查看VIP等级是否达到
        if (!$vipLevel = $this->verify($config['min_vip_level'], 'vip')) {
            C('G_ERROR', 'vip_level_low');
            return false;
        }

        //开始事务
        $this->transBegin();

        //修改数据
        $where['tid'] = $this->mTid;
        $where['day'] = $_POST['day'];
        if (!D('GDailyRegister')->IncreaseData($where, 'status', 1)) {
            goto end;
        }

        //发放奖励
        if (!$this->produce($this->mBonusType[$config['bonus']], $config['bonus_value_1'], $config['bonus_value_2'])) {
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

    //付费领取奖励
    public function receiveNow()
    {

        //查看今天是否已经领取过
        if (!D('GDailyRegister')->isReceived($this->mTid)) {
            C('G_ERROR', 'activity_bonus_free_first');
            return false;
        }

        //查看玩家付费过几次
        $count = D('GDailyRegister')->getPayCount($this->mTid);
        ++$count;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney($this->mEventConfig['exchange'], $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //查看玩家领取过几次
        $day = D('GDailyRegister')->getCount($this->mTid);
        ++$day;

        //查看客户端是否出错
        if ($day != $_POST['day']) {
            C('G_ERROR', 'daily_register_wrong_day');
            return false;
        }

        //查询当日奖励情况
        $utime = get_daily_utime();
        $month = date('n', $utime);//月份
        $config = D('Static')->access('daily_register', $month, $day);
        if (empty($config)) {
            C('G_ERROR', 'activity_bonus_end_this_month');
            return false;
        }

        //未领取
        if ($config['min_vip_level'] == 0) {
            $times = 1;//当日没有双倍
        } else {
            if (!$vipLevel = $this->verify($config['min_vip_level'], 'vip')) {
                $times = 1;//VIP不够则领单倍
            } else {
                $times = 2;//达到VIP等级领取双倍
            }
        }

        //开始事务
        $this->transBegin();

        //插入领取数据
        $data['tid'] = $this->mTid;
        $data['day'] = $day;
        $data['pay'] = 1;
        $data['status'] = $times;
        if (!D('GDailyRegister')->CreateData($data)) {
            goto end;
        }

        //发放奖励
        if (!$this->produce($this->mBonusType[$config['bonus']], $config['bonus_value_1'], $config['bonus_value_2'] * $times)) {
            goto end;
        }

        //扣除货币
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
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