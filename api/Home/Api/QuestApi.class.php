<?php
namespace Home\Api;

use Think\Controller;

class QuestApi extends BaseApi
{

    private $questType = array(
        'story' => '1',
        'yindao' => '2',
    );

    //获取任务列表
    public function getList($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取所有任务列表
        $questConfig = D('Static')->access('quest');

        //获取玩家的主线任务
        $questList = D('GQuest')->getAll($this->mTid);

        //接取任务
        if ($this->accept($questList, $questConfig)) {
            $questList = D('GQuest')->getAll($this->mTid);
        }

        //遍历所有任务
        $list = array();
        foreach ($questConfig as $key => $value) {
            $list['type_' . $key] = array();
            foreach ($value as $k => $val) {
                //如果存在任务
                if (isset($questList[$k]) && $questList[$k] != '1') {
                    switch ($val['target']) {
                        case '1'://通关副本
                            $schedule = $this->schedule('instance', $val['target_value_1']);
                            break;
                        case '2'://战队等级提升
                            $schedule = $this->schedule('level');
                            break;
                        case '3'://指定伙伴(更高品质亦可)
                            $schedule = $this->schedule('partnerId', $val['target_value_1']);
                            break;
                        case '4'://指定品质伙伴总数(更高品质亦可)
                            $schedule = $this->schedule('partnerQualityCount', $val['target_value_1']);
                            break;
                        case '5'://道具
                            $schedule = $this->schedule('item', $val['target_value_1']);
                            break;
                        case '6'://伙伴好感
                            $schedule = floor($this->schedule('favour', $val['target_value_1']) / 1000);
                            break;
                        case '7'://获得伙伴组
                            $schedule = $this->schedule('partnerGroup', $val['target_value_1']);
                            break;
                        case '8'://获得伙伴组
                            $schedule = $this->schedule('partnerLevel', $val['target_value_1']);
                            break;
                    }

                    $questInfo['quest_id'] = $k;
                    $questInfo['schedule'] = $schedule;
                    $list['type_' . $key][] = $questInfo;
                }


            }

        }

        //返回
        return $list;

    }

    //接取主线任务
    private function accept($questList, $questConfig)
    {

        //标记
        $flag = false;

        //获取玩家等级
        $level = D('GTeam')->getAttr($this->mTid, 'level');

        //获取副本完成情况
        $instanceFinish = D('GInstance')->getInstances($this->mTid);

        foreach ($questConfig as $key => $value) {

            foreach ($value as $k => $val) {

                //任务已经完成或接取
                if (isset($questList[$k])) {
                    continue;
                }

                //等级不足
                if ($val['need_level'] > 1 && $val['need_level'] > $level) {
                    continue;
                }

                //没有完成前置副本
                if ($val['pre_instance'] != 0 && !in_array($val['pre_instance'], $instanceFinish)) {
                    continue;
                }

                //没有完成前置任务
                if ($val['pre_quest'] != 0 && (!isset($questList[$val['pre_quest']]) || $questList[$val['pre_quest']] != '1')) {
                    continue;
                }

                //接取任务
                $add['tid'] = $this->mTid;
                $add['quest'] = $k;
                D('GQuest')->CreateData($add);
                $flag = true;

            }

        }

        return $flag;

    }

    //完成任务
    public function complete()
    {

        //获取任务配置
        $questConfig = D('Static')->access('quest', $_POST['quest_type'], $_POST['quest_id']);
        if (empty($questConfig)) {
            C('G_ERROR', 'quest_not_exist');
            return false;
        }
        //获取任务信息
        $where['tid'] = $this->mTid;
        $where['quest'] = $_POST['quest_id'];
        $questInfo = D('GQuest')->getRowCondition($where);
        //查看任务是否有记录
        if (empty($questInfo)) {
            C('G_ERROR', 'quest_not_accept');
            return false;
        }
        //查看任务是否已经完成
        if ($questInfo['status'] == '1') {
            C('G_ERROR', 'quest_complete_already');
            return false;
        }
        //判断任务条件是否满足
        $isComplete = false;
        switch ($questConfig['target']) {

            case '1'://通关副本
                if ($this->verify($questConfig['target_value_2'], 'instance', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

            case '2'://战队等级提升
                if ($this->verify($questConfig['target_value_2'], 'level')) {
                    $isComplete = true;
                }
                break;

            case '3'://指定伙伴(更高品质亦可)
                if ($this->verify($questConfig['target_value_2'], 'partnerId', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

            case '4'://指定品质伙伴总数(更高品质亦可)
                if ($this->verify($questConfig['target_value_2'], 'partnerQualityCount', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

            case '5'://道具
                if ($this->verify($questConfig['target_value_2'], 'item', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

            case '6'://伙伴好感
                if ($this->verify($questConfig['target_value_2'] * 1000, 'favour', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

            case '7'://获得伙伴组
                if ($this->verify($questConfig['target_value_2'], 'partnerGroup', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

            case '8'://获得伙伴等级
                if ($this->verify($questConfig['target_value_2'], 'partnerLevel', $questConfig['target_value_1'])) {
                    $isComplete = true;
                }
                break;

        }

        if (!$isComplete) {
            C('G_ERROR', 'quest_not_complete');
            return false;
        }

        //开始事务
        $this->transBegin();

        //完成任务
        $data['status'] = 1;
        if (false === D('GQuest')->UpdateData($data, $where)) {
            goto end;
        }

        //扣道具
        if ($questConfig['target'] == '5') {
            if (!D('GItem')->dec($this->mTid, $questConfig['target_value_1'], $questConfig['target_value_2'])) {
                goto end;
            }
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

}