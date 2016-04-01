<?php
namespace Home\Api;

use Think\Controller;

class QuestPartnerApi extends BBattleApi
{

    const TARGET_COUNT = 1;
    const NEED_COUNT = 4;

    //查询伙伴任务
    public function getList($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        $list = array();

        //获取所有任务列表
        $questConfig = D('Static')->access('partner_quest_group');

        //获取玩家伙伴信息
        $partnerGroupList = D('GPartner')->getGroups($this->mTid);

        //获取玩家已经完成&正在进行的伙伴任务
        $questList = D('GPartnerQuest')->getAll($this->mTid);

        //自动接取任务
        $flag = $this->accept($questConfig, $partnerGroupList, $questList);

        //如果接到任务则重新获取玩家伙伴任务列表
        if ($flag > 0) {
            $questList = D('GPartnerQuest')->getAll($this->mTid);
        }

        //遍历任务配置
        foreach ($questConfig as $key => $value) {
            if (in_array($key, $partnerGroupList)) {//如果拥有该伙伴
                $arr = array();
                $arr['group'] = $key;//伙伴组ID
                $arr['quest_id'] = -1;
                for ($i = 1; $i <= self::TARGET_COUNT; ++$i) {
                    $arr['target_' . $i . '_schedule'] = 0;
                }
                foreach ($value as $k => $val) {//遍历任务

                    if (isset($questList[$k]) && $questList[$k] == '0') {

                        $arr['quest_id'] = $k;//伙伴任务ID
                        //正在进行的任务显示完成条件&进度
                        for ($i = 1; $i <= self::TARGET_COUNT; ++$i) {
                            switch ($val['target_' . $i]) {//查询进度
                                case '0'://没有条件
                                    $arr['target_' . $i . '_schedule'] = 0;
                                    break;

                                case '1'://通关副本
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('instance', $val['target_' . $i . '_value_1']);
                                    break;

                                case '2'://通关副本组
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('instanceGroup', $val['target_' . $i . '_value_1']);
                                    break;

                                case '3'://伙伴等级
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('partnerLevel', $key);
                                    break;

                                case '4'://获得伙伴
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('partnerId', $val['target_' . $i . '_value_1']);
                                    break;

                                case '5'://获得伙伴数
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('partnerQualityCount', $val['target_' . $i . '_value_1']);
                                    break;

                                case '6'://获得道具
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('item', $val['target_' . $i . '_value_1']);
                                    break;

                                case '7'://伙伴好感度
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('favour', $val['target_' . $i . '_value_1']);
                                    break;

                                case '8'://获得伙伴
                                    $arr['target_' . $i . '_schedule'] = $this->schedule('partnerId', $val['target_' . $i . '_value_1']);
                                    break;
                            }

                        }
                        break;
                    }

                }
                $list[] = $arr;
            }
        }

        return $list;

    }

    //接受伙伴任务
    public function accept($questConfig = array(), $partnerGroupList = array(), $questList = array())
    {
        //获取所有任务列表
        if (empty($questConfig)) {
            $questConfig = D('Static')->access('partner_quest_group');
        }

        //获取玩家伙伴信息
        if (empty($partnerGroupList)) {
            $partnerGroupList = D('GPartner')->getGroups($this->mTid);
        }

        //获取玩家已经完成&正在进行的伙伴任务
        if (empty($questList)) {
            $questList = D('GPartnerQuest')->getAll($this->mTid);
        }

        $flag = 0;
        //遍历所有任务配置
        foreach ($questConfig as $key => $value) {
            //存在伙伴则继续
            if (in_array($key, $partnerGroupList)) {

                //遍历伙伴任务
                foreach ($value as $k => $val) {

                    if (isset($questList[$k])) {
                        if ($questList[$k] == '1') {
                            continue;
                        }
                        if ($questList[$k] == '0') {
                            break;
                        }
                    } else {

                        $isComplete = true;//条件满足标识

                        //循环4个接取任务条件
                        for ($i = 1; $i <= self::NEED_COUNT; ++$i) {

                            //条件类型
                            switch ($val['need_' . $i . '_type']) {

                                case '0'://无要求
                                    break;

                                case '1'://完成普通任务
                                    if (!$this->verify(1, 'quest', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '2'://通关副本
                                    if (!$this->verify($val['need_' . $i . '_value_2'], 'instance', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '3'://伙伴等级
                                    if (!$this->verify($val['need_' . $i . '_value_2'], 'partnerLevel', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '4'://获得伙伴
                                    if (!$this->verify(1, 'partnerId', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '5'://获得伙伴数
                                    if (!$this->verify($val['need_' . $i . '_value_2'], 'partnerQualityCount', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '6'://伙伴好感度
                                    if (!$this->verify($val['need_' . $i . '_value_2'], 'favour', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '7'://获得伙伴
                                    if (!$this->verify(1, 'partnerId', $val['need_' . $i . '_value_1']))
                                        $isComplete = false;
                                    break;

                                case '8'://完成伙伴任务
                                    if ($questList[$val['need_' . $i . '_value_1']] != '1')//查看是否已经完成了伙伴任务
                                        $isComplete = false;
                                    break;

                            }

                            if ($isComplete == false) {
                                break;
                            }

                        }

                        if ($isComplete === true) {
                            //接取任务
                            $add['tid'] = $this->mTid;
                            $add['quest'] = $k;
                            if (false === D('GPartnerQuest')->CreateData($add)) {
                                return false;
                            }
                            ++$flag;
                        }

                        break;

                    }

                }

            }

        }

        return $flag;

    }

    //完成伙伴任务
    public function complete()
    {

        //接取任务
        $this->accept();

        //查询任务情况
        $where['tid'] = $this->mTid;
        $where['quest'] = $_POST['quest_id'];
        $quest = D('GPartnerQuest')->getRowCondition($where);

        //没有接任务
        if (empty($quest)) {
            C('G_ERROR', 'quest_not_accept');
            return false;
        }

        //任务已经完成
        if ($quest['status'] == '1') {
            C('G_ERROR', 'quest_complete_already');
            return false;
        }

        //获取任务信息
        $questConfig = D('Static')->access('partner_quest', $_POST['quest_id']);
        if (empty($questConfig)) {
            C('G_ERROR', 'quest_not_exist');
            return false;
        }

        //循环3个完成任务条件
        $isComplete = true;
        for ($i = 1; $i <= self::TARGET_COUNT; ++$i) {

            //条件类型
            switch ($questConfig['target_' . $i]) {

                case '0'://没有条件
                    break;

                case '1'://通关副本
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'instance', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

                case '2'://通关副本组
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'instanceGroup', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

                case '3'://伙伴等级
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'partnerLevel', $questConfig['belong_partner'])) {
                        $isComplete = false;
                    }
                    break;

                case '4'://获得伙伴
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'partnerId', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

                case '5'://获得伙伴数
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'partnerQualityCount', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

                case '6'://获得道具
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'item', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

                case '7'://伙伴好感度
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'favour', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

                case '8'://获得伙伴
                    if (!$this->verify($questConfig['target_' . $i . '_value_2'], 'partnerId', $questConfig['target_' . $i . '_value_1'])) {
                        $isComplete = false;
                    }
                    break;

            }

            if ($isComplete === false) {
                break;
            }

        }

        //任务未完成
        if ($isComplete === false) {
            C('G_ERROR', 'quest_not_complete');
            return false;
        }

        //开始事务
        $this->transBegin();

        //完成任务
        $data['status'] = 1;
        if (false === D('GPartnerQuest')->UpdateData($data, $where)) {
            goto end;
        }

        //扣道具
        for ($i = 1; $i <= 3; ++$i)
            if ($questConfig['target_' . $i] == '7')
                if (!$this->recover('item', $questConfig['target_' . $i . '_value_1'], $questConfig['target_' . $i . '_value_2'])) {
                    goto end;
                }

        //获得奖励
        if (!$this->bonus($questConfig)) {
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

    //开始副本
    public function fight()
    {

        //查看副本是否已经胜利过
        $count = D('GInstance')->getCount($this->mTid, $_POST['instance_id']);
        if ($count > 0) {
            C('G_ERROR', 'instance_completed');
            return false;
        }
        return $this->instanceFight('QuestPartner');
    }

    //开始副本
    public function win()
    {
        return $this->instanceWin();
    }

    //开始副本
    public function lose()
    {
        return $this->instanceLose();
    }


    /*//查询伙伴任务
    public function getTotalList()
    {

        //自动接受伙伴任务
        $this->accept($questList, $questConfig);

        //任务列表
        $list = array();
        foreach ($questConfig as $key => $value) {

            //如果任务不能接取
            if (!isset($questList[$key])) {
                $list['lock'][] = $key;
            }

            //如果任务已经完成
            if (isset($questList[$key]) && $questList[$key] == 1) {
                $list['done'][] = $key;
            }

            //如果任务已接取未完成
            if (isset($questList[$key]) && ($questList[$key] == 0 || $questList[$key] == 2)) {

                $questInfo['quest_id'] = $key;
                $questInfo['status'] = $questList[$key];

                //正在进行的任务显示完成条件&进度
                for ($i = 1; $i <= 3; ++$i) {
                    switch ($value['target_' . $i]) {//查询进度
                        case '0'://没有条件
                            $questInfo['target_' . $i . '_schedule'] = 0;
                            break;

                        case '1'://通关副本
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('instance', $value['target_' . $i . '_value_1']);
                            break;

                        case '2'://通关副本组
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('instanceGroup', $value['target_' . $i . '_value_1']);
                            break;

                        case '3'://伙伴等级
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('partnerLevel', $_POST['group']);
                            break;

                        case '4'://获得伙伴
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('partnerId', $value['target_' . $i . '_value_1']);
                            break;

                        case '5'://获得伙伴数
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('partnerQualityCount', $value['target_' . $i . '_value_1']);
                            break;

                        case '6'://获得道具
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('item', $value['target_' . $i . '_value_1'], $recordTime);
                            break;

                        case '7'://伙伴好感度
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('favour', $value['target_' . $i . '_value_1']);
                            break;

                        case '8'://获得伙伴
                            $questInfo['target_' . $i . '_schedule'] = $this->schedule('partnerId', $value['target_' . $i . '_value_1']);
                            break;
                    }

                }

                $list['doing'][] = $questInfo;
                unset($questInfo);

            }

        }

        //返回
        return $list;

    }

    //查看任务情况
    public function check()
    {

        //查询任务情况
        $where['tid'] = $this->mTid;
        $where['quest'] = $_POST['quest_id'];
        $quest = D('GPartnerQuest')->getRowCondition($where);
        if (empty($quest)) {
            C('G_ERROR', 'quest_not_accept');//没有接任务
            return false;
        }

        //任务是否已完成
        if ($quest['status'] == '1') {
            C('G_ERROR', 'quest_complete_already');//任务已经完成
            return false;
        }

        //已读
        if ($quest['status'] == '2') {
            return true;
        }

        //修改状态
        $data['status'] = 2;
        return D('GPartnerQuest')->UpdateData($data, $where);

    }*/

}