<?php
namespace Home\Api;

use Think\Controller;

class QuestDailyApi extends BaseApi
{

    const BUY_GOLD_COUNT = 2;
    const BUY_VALITY_COUNT = 3;

    //获取任务列表
    public function getList($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取所有任务列表
        $questConfig = D('Static')->access('quest_daily');

        //获取玩家的每日任务
        $questList = D('TDailyQuest')->getAll($this->mTid);

        //接取任务
        if ($this->accept($questList, $questConfig)) {
            $questList = D('TDailyQuest')->getAll($this->mTid);
        }

        //遍历所有任务
        $list = array();
        foreach ($questConfig as $key => $value) {
            //如果存在任务
            if (isset($questList[$key]) && $questList[$key] < $value['quest_count']) {
                $finishTime = isset($questList[$key]) ? $questList[$key]['count'] : 0;
                $schedule = $this->dailySchedule($value);
                $schedule = $schedule - ($finishTime * $value['target_value_2']);
                $info['quest_id'] = $key;
                $info['schedule'] = $schedule;
                $list[] = $info;
            }
        }

        //返回
        return $list;

    }

    //接取每日任务
    private function accept($questList = array(), $questConfig = array())
    {
        $now = time();

        //获取玩家的每日任务
        if (empty($questList)) {
            $questList = D('TDailyQuest')->getAll($this->mTid);
        }

        //获取所有任务列表
        if (empty($questConfig)) {
            $questConfig = D('Static')->access('quest_daily');
        }

        //获取玩家等级
        $level = D('GTeam')->getAttr($this->mTid, 'level');

        //获取副本完成情况
        $instanceFinish = D('GInstance')->getInstances($this->mTid);

        //获取引导完成情况
        $guideFinish = D('GGuide')->getComplete($this->mTid);

        //遍历任务
        $all = array();
        foreach ($questConfig as $key => $value) {

            //任务已经完成或接取
            if (isset($questList[$key])) {
                continue;
            }

            //等级不足
            if ($value['need_level'] > 1 && $value['need_level'] > $level) {
                continue;
            }

            //没有完成前置副本
            if ($value['pre_instance'] != 0 && !in_array($value['pre_instance'], $instanceFinish)) {
                continue;
            }

            //没有完成前置任务
            if ($value['pre_quest'] != 0 && (!isset($questList[$key]) || $questList[$key] != '1')) {
                continue;
            }

            //没有完成引导内容
            if ($value['pre_guide_seq'] > 0) {
                if (!isset($guideFinish[$value['pre_guide_seq']])) {
                    continue;
                } else {
                    $guideKey = D('Static')->access('guide_seq', $value['pre_guide_seq'], 'key_step');
                    if ($guideKey > $guideFinish[$value['pre_guide_seq']]) {
                        continue;
                    }
                }
            }

            //接取任务
            $add['tid'] = $this->mTid;
            $add['quest'] = $key;
            $add['count'] = 0;
            $add['ctime'] = $now;
            $add['utime'] = $now;
            $all[] = $add;
        }

        //操作数据库
        if (!empty($all) && false === D('TDailyQuest')->CreateAllData($all)) {
            return false;
        }

        //返回
        return true;

    }

    //完成任务
    public function complete()
    {

        //获取任务配置
        $questConfig = D('Static')->access('quest_daily', $_POST['quest_id']);
        if (empty($questConfig)) {
            C('G_ERROR', 'quest_not_exist');
            return false;
        }

        //获取任务信息
        $where['tid'] = $this->mTid;
        $where['quest'] = $_POST['quest_id'];
        $questInfo = D('TDailyQuest')->getRowCondition($where);

        //查看任务是否有记录
        if (empty($questInfo)) {
            C('G_ERROR', 'quest_not_accept');
            return false;
        }

        //查看任务是否已经完成
        if ($questInfo['count'] >= $questConfig['quest_count']) {
            C('G_ERROR', 'quest_count_max');
            return false;
        }

        //判断任务条件是否满足
        $schedule = $this->dailySchedule($questConfig);
        $schedule = $schedule - ($questInfo['count'] * $questConfig['target_value_2']);
        if ($questConfig['target_value_2'] > $schedule) {
            C('G_ERROR', 'quest_not_complete');
            return false;
        }

        //开始事务
        $this->transBegin();

        //完成任务
        if (!D('TDailyQuest')->IncreaseData($where, 'count')) {
            goto end;
        }

        //获得奖励
        if (!$this->bonus($questConfig)) {
            goto end;
        }

        //是会员奖励，则需要特别记录
        if ($questConfig['target'] == '15') {
            if (false === D('GMember')->receive($this->mTid, 1)) {
                goto end;
            }
            if (false === D('LMember')->cLog($this->mTid, 1)) {
                goto end;
            }
        } else if ($questConfig['target'] == '17') {
            if (false === D('GMember')->receive($this->mTid, 2)) {
                goto end;
            }
            if (false === D('LMember')->cLog($this->mTid, 2)) {
                goto end;
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //记录每日任务完成
        D('GCount')->incAttr($this->mTid, 'quest_daily');

        //返回
        return true;

    }

    //进度查询
    private function dailySchedule($config)
    {

        $schedule = 0;
        switch ($config['target']) {

            case '1'://通关副本
                $schedule = D('LInstance')->getTodayCount($this->mTid, $config['target_value_1']);

                break;
            case '2'://通关副本组
                $schedule = D('LInstance')->getTodayGroupCount($this->mTid, $config['target_value_1']);
                break;

            case '3'://通关副本难度
                $schedule = D('LInstance')->getTodayDifficultyCount($this->mTid, $config['target_value_1']);
                break;

            case '4'://竞技场挑战
                $schedule = D('TDailyEvent')->getTodayArenaCount($this->mTid);
                break;

            case '5'://通天塔次数
                $schedule = D('LInstance')->getTodayBabelCount($this->mTid);
                break;

            case '6'://升级装备
                $schedule = D('LEquipStrengthen')->getTodayCount($this->mTid);
                break;

            case '7'://升级技能
                $schedule = D('LPartner')->getTodaySkillLevelupCount($this->mTid);
                break;

            case '8'://抽卡
                $schedule = D('LPray')->getTodayCount($this->mTid, $config['target_value_1']);
                break;

            case '9'://充值
                $schedule = D('LOrder')->getTodayCount($this->mTid);
                break;

            case '10'://分享
                $schedule = D('LShare')->getTodayCount($this->mTid);
                break;

            case '11'://购买金币次数
                $schedule = D('TDailyCount')->getCount($this->mTid, self::BUY_GOLD_COUNT);
                break;

            case '12'://生死门
                $schedule = D('LDynamic')->getTodayLifeDeathCount($this->mTid);
                break;

            case '13'://装备升阶
                $schedule = D('LEquipUpgrade')->getTodayCount($this->mTid);
                break;

            case '14'://购买体力
                $schedule = D('TDailyCount')->getCount($this->mTid, self::BUY_VALITY_COUNT);
                break;

            case '15'://月卡会员奖励
                $schedule = D('GMember')->expireDay($this->mTid, 1);
                break;

            case '17'://年卡会员奖励
                $schedule = D('GMember')->expireDay($this->mTid, 2);
                break;

        }

        return $schedule;

    }

}