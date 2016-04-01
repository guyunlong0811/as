<?php
namespace Home\Api;

use Think\Controller;

class BEventApi extends BBattleApi
{

    //活动相关
    protected $mEventInfo;//活动Ps
    protected $mEventConfig;//活动配置
    protected $mEventBuyCount;//活动购买次数
    protected $mEventRemainCount;//活动剩余参加次数

    //活动参加条件检查(时间&等级)
    protected function event($group)
    {

        //获取活动信息
        $eventRedis = D('Predis')->cli('game')->hgetall('event:' . $group);

        //是否开启
        if (false === $this->isOpen($group, $eventRedis)) {
            C('G_ERROR', 'activity_not_in_open_time');
            return false;
        }

        //获取活动配置
        $this->mEventConfig = D('Static')->access('event', $group, $eventRedis['index']);

        //查看是否达到了开放条件
        if ($this->mEventConfig['open_process'] != 0) {
            if (!D('SOpenProcess')->checkOpen($this->mTid, $this->mEventConfig['open_process'])) {
                return false;
            }
        }

        //活动类型
        switch ($this->mEventConfig['type']) {
            case '0':
            case '1':
                $this->mEventConfig['tablename'] = 'TDailyEvent';
                break;
            case '2':
                $this->mEventConfig['tablename'] = 'TWeeklyEvent';
                break;
            case '3':
                $this->mEventConfig['tablename'] = 'TSpecifyEvent';
                break;
        }
        $this->mEventBuyCount = $this->getEventBuyCount();
        $this->mEventRemainCount = $this->getEventRemainCount();

//        dump($this->mEventInfo);
//        dump($this->mEventConfig);
//        dump($this->mEventBuyCount);
//        dump($this->mEventRemainCount);

        return true;

    }

    //检查活动是否开启
    public function isOpen($group, $eventRedis = null)
    {

        //获取活动信息
        if (is_null($eventRedis)) {
            $eventRedis = D('Predis')->cli('game')->hgetall('event:' . $group);
        }

        //检查活动是否开放
        if (empty($eventRedis)) {
            $where['group'] = $group;
            $where['status'] = 1;
            $eventInfo = M('GEvent')->where($where)->find();
            if (empty($eventInfo)) {
                $event['status'] = 0;
                $event['index'] = 0;
                $event['ps'] = '';
                D('Predis')->cli('game')->hmset('event:' . $group, $event);
                return false;
            }
            $event['status'] = 1;
            $event['index'] = $eventInfo['id'];
            $event['ps'] = $eventInfo['ps'];
            D('Predis')->cli('game')->hmset('event:' . $group, $event);
            $this->mEventInfo = json_decode($eventInfo['ps'], true);
        } else {
            if ($eventRedis['status'] == '0') {
                return false;
            } else {
                $this->mEventInfo = json_decode($eventRedis['ps'], true);
            }
        }

        //返回
        return true;
    }

    //获取活动购买次数
    protected function getEventBuyCount()
    {
        /*
         * 1 使用次数
         * 2 购买次数
         * 3 增加次数
         * 4 附加功能使用次数
         */
        if ($this->mEventConfig['group'] == '15') {
            return 0;
        }
        if ($this->mEventConfig['exchange'] == '0') {
            return 0;
        }
        //判断次数计算方式，获取次数
        switch ($this->mEventConfig['count_type']) {
            case '1':
                return D($this->mEventConfig['tablename'])->getCount($this->mTid, $this->mEventConfig['index'], 2);
            case '2':
                return D($this->mEventConfig['tablename'])->getGroupCount($this->mTid, $this->mEventConfig['group'], 2);
            default:
                return 0;
        }

    }

    //获取剩余参加活动次数
    protected function getEventRemainCount()
    {

        //不限次数
        if ($this->mEventConfig['count_type'] == 0) {
            return true;
        }

        //特殊情况
        switch ($this->mEventConfig['group']) {
            case '4'://深渊之战
            case '8'://新手登录
            case '9'://每日签到
            case '15'://公会战
                return 0;
        }

        //判断次数计算方式，获取次数
        $used = 0;
        switch ($this->mEventConfig['count_type']) {
            case '1':
                $used = D($this->mEventConfig['tablename'])->getCount($this->mTid, $this->mEventConfig['index'], 1);
                break;
            case '2':
                $used = D($this->mEventConfig['tablename'])->getGroupCount($this->mTid, $this->mEventConfig['group'], 1);
                break;
        }

        $add = 0;
        switch ($this->mEventConfig['group']) {

            case '2'://通天塔
            case '10'://体力发放
            case '14'://生死门
                $remain = $this->mEventConfig['count'] - $used;
                return $remain;
            case '7'://失灭之战
                switch ($this->mEventConfig['count_type']) {
                    case '1':
                        $add = D($this->mEventConfig['tablename'])->getCount($this->mTid, $this->mEventConfig['index'], 3);
                        break;
                    case '2':
                        $add = D($this->mEventConfig['tablename'])->getGroupCount($this->mTid, $this->mEventConfig['group'], 3);
                        break;
                }
                $remain = $this->mEventConfig['count'] + $add - $used;
                return $remain;
//            case '15':
            case '1'://竞技场
            case '5'://体力试炼
            case '16'://纹章试炼
            case '17'://英雄试炼
            case '18'://装备试炼
            case '19'://猫的报恩
            case '20'://九命之喵
                switch ($this->mEventConfig['count_type']) {
                    case '1':
                        $add = D($this->mEventConfig['tablename'])->getCount($this->mTid, $this->mEventConfig['index'], 2);
                        break;
                    case '2':
                        $add = D($this->mEventConfig['tablename'])->getGroupCount($this->mTid, $this->mEventConfig['group'], 2);
                        break;
                }
                $remain = $this->mEventConfig['count'] + $add - $used;
                return $remain;
            default:
                return -1;

        }

    }

    //购买活动次数
    protected function buyEventCount($target = null)
    {
        if (false === D($this->mEventConfig['tablename'])->record($this->mTid, $this->mEventConfig['index'], 2, $this->mEventConfig['group'])) {
            return false;
        }

        if ($this->mEventConfig['group'] == 7) {
            if (empty($target)) {
                $target = $this->mTid;
            }
            if (false === D($this->mEventConfig['tablename'])->record($target, $this->mEventConfig['index'], 3, $this->mEventConfig['group'])) {
                return false;
            }
        }
        return true;
    }

    //使用活动次数
    protected function useEventCount()
    {
        return D($this->mEventConfig['tablename'])->record($this->mTid, $this->mEventConfig['index'], 1, $this->mEventConfig['group']);
    }

}