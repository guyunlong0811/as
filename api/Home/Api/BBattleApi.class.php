<?php
namespace Home\Api;

use Think\Controller;

class BBattleApi extends BaseApi
{

    private $mInstanceInfoConfig;//配置
    private $mInstanceInfo;//副本信息
    protected $mValityNow;//现在的体力数
    protected $mDropItemList = array();//胜利后获得掉落物品列表
    protected $mComboRecordList = array();//主线副本连击记录
    private $mBattleInfo;//对战副本信息
    private $mBattleConfig;//对战副本配置

    //战斗结算
    protected function spoils($type, $params1, $params2 = null)
    {

        switch ($type) {

            //战队属性
            case 'diamond'://水晶
                $arr['type'] = 2;
                $arr['id'] = 3001;
                $arr['count'] = $params1;
                break;
            case 'gold'://金币
                $arr['type'] = 2;
                $arr['id'] = 3002;
                $arr['count'] = $params1;
                break;
            case 'honour'://荣誉值
                $arr['type'] = 2;
                $arr['id'] = 3003;
                $arr['count'] = $params1;
                break;
            case 'contribution'://贡献度
                $arr['type'] = 2;
                $arr['id'] = 3004;
                $arr['count'] = $params1;
                break;


            //活动属性
            case 'kit_1'://锦囊1
                $arr['type'] = 2;
                $arr['id'] = 6001;
                $arr['count'] = $params1;
                break;
            case 'kit_2'://锦囊2
                $arr['type'] = 2;
                $arr['id'] = 6002;
                $arr['count'] = $params1;
                break;
            case 'kit_3'://锦囊3
                $arr['type'] = 2;
                $arr['id'] = 6003;
                $arr['count'] = $params1;
                break;

        }

        return $arr;

    }

    //怪物掉落
    protected function loot($config)
    {
        //计算怪物掉落情况
        $lootList = array();//怪物掉落
        $boxList = array();//掉落宝箱集合
        for ($i = 1; $i <= 3; ++$i) {

            if (!empty($config['battle_' . $i . '_monster'])) {//战役为空

                $monster = explode('#', $config['battle_' . $i . '_monster']);
                foreach ($monster as $value) {

                    //获取怪物配置
                    $monsterConfig = D('Static')->access('monster', $value);

                    //物品ID
                    $loot = $monsterConfig['loot_item'];

                    //获取权重
                    $loot0 = $monsterConfig['loot_0_count'];
                    $loot1 = $monsterConfig['loot_1_count'];
                    $loot2 = $monsterConfig['loot_2_count'];
                    $loot3 = $monsterConfig['loot_3_count'];
                    $loot4 = $monsterConfig['loot_4_count'];

                    //计算掉落数字
                    $lootNum = 0;
                    $lootAll = $loot0 + $loot1 + $loot2 + $loot3 + $loot4;
                    if ($lootAll > 0) {//如果怪物没有掉落
                        //计算概率
                        $rand = rand(1, $lootAll);
                        if ($rand <= $loot4) {
                            $lootNum = '4';
                        } else if ($rand <= $loot3 + $loot4) {
                            $lootNum = '3';
                        } else if ($rand <= $loot2 + $loot3 + $loot4) {
                            $lootNum = '2';
                        } else if ($rand <= $loot1 + $loot2 + $loot3 + $loot4) {
                            $lootNum = '1';
                        } else {
                            $lootNum = '0';
                        }
                        //如果有掉落物品
                        if ($lootNum > 0) {
                            $boxList[$loot] += $lootNum;
                        }

                    }

                    $lootList[] = $lootNum;

                }

            }

        }

        //返回
        $return['list'] = $lootList;
        $return['box'] = $boxList;
        return $return;
    }

    //获取掉落道具
    protected function getLoot($drop, $module, $index = 0)
    {

        if (!is_array($drop)) {
            //解析json
            $dropList = json_decode($drop, true);
            if (empty($dropList)) {
                return array();
            }
        } else {
            $dropList = $drop;
        }

        //区分普通&精英副本
        if ($module == 'Instance') {
            $difficult = substr($index, 0, 1);
            if ($difficult == '1') {
                $module == 'InstanceNormal';
            } else if ($difficult == '2') {
                $module == 'InstanceAdvance';
            } else if ($difficult == '7') {
                $module == 'InstanceItem';
            }
        }

        //获取当前模式掉率
        $channel = $this->mSessionInfo['channel_id'];
        $rate = D('DOperation')->getRate(C('G_SID'), $channel, $module);

        //遍历宝箱
        $rewardList = array();
        $list = array();
        foreach ($dropList as $key => $value) {

            for ($i = 1; $i <= $value; ++$i) {

                $reward = $this->produce('box', $key, $rate);
                if (!empty($reward)) {

                    foreach ($reward as $val) {
                        if (isset($rewardList[$val['type']][$val['id']])) {
                            $rewardList[$val['type']][$val['id']] += $val['count'];
                        } else {
                            $rewardList[$val['type']][$val['id']] = $val['count'];
                        }
                    }

                }

            }

        }

        //重新整理
        foreach ($rewardList as $key => $value) {
            foreach ($value as $k => $val) {
                $arr['type'] = $key;
                $arr['id'] = $k;
                $arr['count'] = $val;
                $list[] = $arr;
            }
        }

        //返回
        return $list;

    }

    //获取战斗数据
    protected function getFightInfo($tid, $partnerFightList)
    {

        $info = array();

        //获取星位情况
        $starList = D('GStar')->getAll($tid);
        $partnerStarList = array();
        foreach ($starList as $value) {
            $partnerStarList[] = $value['partner'];
        }

        //查询伙伴基本信息
        $partnerArr = array_merge($partnerFightList, $partnerStarList);
        $fieldPartner = array('group', 'index', 'level', 'favour', 'force', 'skill_1_level', 'skill_2_level', 'skill_3_level', 'skill_4_level', 'skill_5_level', 'skill_6_level',);
        $partnerSelect = D('GPartner')->getAll($tid, $fieldPartner, $partnerArr);

        //获取伙伴武器信息
        $equipList = D('GEquip')->getPartnersList($tid, $partnerArr);
        $emblemList = D('GEmblem')->getPartnersList($tid, $partnerArr);
        $partnerList = array();
        foreach ($partnerSelect as $key => $value) {
            $value['equip_list'] = $equipList[$value['group']];
            $value['emblem_list'] = empty($emblemList[$value['group']]) ? array() : $emblemList[$value['group']];
            $partnerList[] = $value;
        }

        //整理数据
        $info['partner_fight'] = $partnerFightList;
        $info['star'] = $starList;
        $info['partner_list'] = $partnerList;
        $fight_info = D('GTeam')->getFightInfo($tid);
        $info = $info + $fight_info;

        //返回
        return $info;

    }

    //获取竞技场对手信息
    protected function getArenaDefense($info)
    {
        $partnerList = json_decode($info['partner'], true);
        return $this->getFightInfo($info['tid'], $partnerList);
    }

    //验证伙伴情况
    protected function verifyPartner($module)
    {
        $select = D('G' . $module . 'Partner')->getAll($this->mTid);//查询数据
        if (empty($select)) {//如果没有数据
            foreach ($_POST['partner'] as $key => $value) {
                if ($value['init_hp'] > $value['hp']) {
                    return false;
                }//hp不能超过上限
                if ($value['init_xp'] != 0) {
                    return false;
                }//第一次玩xp不是0则报错
            }
        } else {
            foreach ($select as $key => $value) {//整理数据
                $list[$value['group']] = $value;
            }
            foreach ($_POST['partner'] as $key => $value) {
                if (isset($list[$value['group']])) {//如果有数据
                    if ($list[$value['group']]['hp'] != $value['init_hp']) {
                        return false;
                    }//检查hp是否一致
                    if ($list[$value['group']]['xp'] != $value['init_xp']) {
                        return false;
                    }//检查xp是否一致
                } else {
                    if ($value['init_hp'] > $value['hp']) {
                        return false;
                    }//hp不能超过上限
                    if ($value['init_xp'] != 0) {
                        return false;
                    }//第一次玩xp不是0则报错
                }
            }
        }
        return true;
    }

    //清除所有当前未完成战斗
    private function cleanFight()
    {
        //PVP
        $dynamicInfo = D('Predis')->cli('fight')->hgetall('dyn:t:' . $this->mTid);
        if (!empty($dynamicInfo)) {
            $this->dynamicLose($dynamicInfo['index']);
        }
    }

    /******************************************************** 副本 Instance 开始 ********************************************************/
    //获取副本难度
    protected function getInstanceDiff($instance)
    {
        $difficulty = 0;
        $mapConfig = D('Static')->access('map');
        foreach ($mapConfig as $value) {
            $arr = explode('#', $value['instance']);
            if (in_array($instance, $arr)) {
                $difficulty = $value['difficulty'];
                break;
            }
        }
        return $difficulty;
    }

    //开始副本
    protected function instanceFight($module = 'Instance', $index = null, $partner = null)
    {

        //检查参数
        if (empty($index)) {
            $index = $_POST['instance_id'];
        }

        if (empty($partner)) {
            $partner = $_POST['partner'];
        }

        //查询副本配置
        $this->mInstanceInfoConfig = D('Static')->access('instance_info', $index);
        if (empty($this->mInstanceInfoConfig)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //查询副本完成列表
        if ($this->mInstanceInfoConfig['pre_instance'] != '0') {
            $count = D('GInstance')->getCount($this->mTid, $this->mInstanceInfoConfig['pre_instance']);
            if ($count == '0') {
                C('G_ERROR', 'pre_instance_not_complete');
                return false;
            }
        }

        //查询体力是否足够
        if (!$this->mValityNow = $this->verify($this->mInstanceInfoConfig['need_vality'], 'vality')) {
            return false;
        }

        //查询玩家当前道具
        if (!$this->verify($this->mInstanceInfoConfig['need_item_count'], 'item', $this->mInstanceInfoConfig['need_item'])) {
            return false;
        }

        //查询今天进入副本的次数
        if ($this->mInstanceInfoConfig['create_times'] != '-1') {
            $count = D('LInstance')->getTodayCount($this->mTid, $index);
            $reset_time = D('TDailyInstance')->getCount($this->mTid, $index);
            $remain = $this->mInstanceInfoConfig['create_times'] * (1 + $reset_time) - $count;
            if ($remain <= 0) {
                C('G_ERROR', 'instance_count_max_today');
                return false;
            }
        }

        //获取战斗剧情开关
        $win = D('GInstance')->getCount($this->mTid, $index);
        $return['story'] = $win > 0 ? 0 : 1;

        //计算怪物掉落情况
        switch ($module) {
            //世界BOSS模式
            case 'AbyssBattle':
            case 'LeagueFight':
            case 'LeagueBoss':
                $battle['drop'] = '[]';
                break;
            //普通副本模式
            default:
                //怪物掉落
                $loot = $this->loot($this->mInstanceInfoConfig);
                $return['loot'] = $loot['list'];
                //首次胜利奖励
                if ($win == 0) {
                    if ($this->mInstanceInfoConfig['first_bonus'] > 0) {
                        $loot['box'][$this->mInstanceInfoConfig['first_bonus']] += 1;
                    }
                }
                $battle['drop'] = json_encode($loot['box']);
        }

        //开始新的副本
        $battle['index'] = $index;
        $battle['module'] = $module;
        $battle['partner'] = json_encode($partner);
        $battle['ctime'] = time();
        D('Predis')->cli('fight')->hmset('ins:t:' . $this->mTid, $battle);
        D('Predis')->cli('fight')->expire('ins:t:' . $this->mTid, get_config('REDIS_TOKEN_TIME'));

        //扣除道具
        if (!$this->recover('item', $this->mInstanceInfoConfig['need_item'], $this->mInstanceInfoConfig['need_item_count'])) {
            return false;
        }

        //获取战斗信息
        $return['player'] = $this->getFightInfo($this->mTid, $partner);

        //返回
        return $return;

    }

    //副本胜利
    protected function instanceWin($index = null)
    {

        if (empty($index)) {
            $index = $_POST['instance_id'];
        }

        $result = 1;//战斗结果

        //查询副本开始记录
        $this->mInstanceInfo = D('Predis')->cli('fight')->hgetall('ins:t:' . $this->mTid);
        if (empty($this->mInstanceInfo)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //副本是否一致
        if ($this->mInstanceInfo['index'] != $index) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //获取副本配置
        $this->mInstanceInfoConfig = D('Static')->access('instance_info', $index);

        //参战伙伴
        $_POST['partner'] = json_decode($this->mInstanceInfo['partner'], true);

        //开始事务
        $this->transBegin();

        //结算
        if (!$this->instanceSettle($result)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //数据异常返回
        if ($result == -1) {
            C('G_ERROR', 'battle_anomaly');
            return false;
        }

        //返回获得道具
        return $this->mDropItemList;

    }

    //副本失败
    protected function instanceLose($index = null)
    {

        if (empty($index)) {
            $index = $_POST['instance_id'];
        }

        $result = 0;//战斗结果

        //查询副本开始记录
        $this->mInstanceInfo = D('Predis')->cli('fight')->hgetall('ins:t:' . $this->mTid);
        if (empty($this->mInstanceInfo)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //副本是否一致
        if ($this->mInstanceInfo['index'] != $index) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //获取副本配置
        $this->mInstanceInfoConfig = D('Static')->access('instance_info', $index);

        //参战伙伴
        $_POST['partner'] = json_decode($this->mInstanceInfo['partner'], true);

        //开始事务
        $this->transBegin();

        //结束副本
        if (!$this->instanceSettle($result)) {
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

    //副本结束
    protected function instanceEnd($index)
    {

        $result = 2;//战斗结果

        //查询副本开始记录
        $this->mInstanceInfo = D('Predis')->cli('fight')->hgetall('ins:t:' . $this->mTid);
        if (empty($this->mInstanceInfo)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //副本是否一致
        if ($this->mInstanceInfo['index'] != $index) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //获取副本配置
        $this->mInstanceInfoConfig = D('Static')->access('instance_info', $index);

        //参战伙伴
        $_POST['partner'] = json_decode($this->mInstanceInfo['partner'], true);

        //开始事务
        $this->transBegin();

        //结束副本
        if (!$this->instanceSettle($result)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //数据异常返回
        if ($result == -1) {
            //返回
            C('G_ERROR', 'battle_anomaly');
            return false;
        }

        //返回获得道具
        return true;

    }

    private function instanceSettle($result)
    {

        //验证战斗时间
        if(!$this->verifyDuration($_POST['duration'], $this->mInstanceInfo['ctime'])){
            $result = -1;
        }

        //战斗胜利
        if ($result == 1 || $result == 2) {

            //扣除体力
            if (!$this->recover('vality', $this->mInstanceInfoConfig['need_vality'], null, $this->mValityNow)) {
                return false;
            }

            //奖励加成
            $bonusGold = $this->mInstanceInfoConfig['bonus_gold'];
            $bonusTeamExp = $this->mInstanceInfoConfig['bonus_team_exp'];
            $bonusPartnerExp = $this->mInstanceInfoConfig['bonus_partner_exp'];

            //金币奖励
            if ($result == 1) {
                if (!$this->produce('gold', $bonusGold)) {
                    return false;
                }
            }

            //战队经验（先加战队经验）
            if (false === $this->produce('teamExp', $bonusTeamExp)) {
                return false;
            }

            //伙伴经验
            $partnerFight = json_decode($this->mInstanceInfo['partner'], true);
            foreach ($partnerFight as $value) {
                if (false === $this->produce('partnerExp', $value, $bonusPartnerExp)) {
                    return false;
                }
            }

            //发放掉落奖励
            if (false === $this->mDropItemList = $this->getLoot($this->mInstanceInfo['drop'], $this->mInstanceInfo['module'], $this->mInstanceInfo['index'])) {
                return false;
            }

            //副本完成总次数+1
            if (!D('GInstance')->complete($this->mTid, $this->mInstanceInfoConfig['index'], 1, $_POST['star'], $_POST['combo'])) {
                return false;
            }

            //记录当日副本最高combo
            D('TDailyCount')->record($this->mTid, 6, $_POST['combo']);

            //记录成就
            D('GCount')->combo($this->mTid, $_POST['combo']);

            //如果combo数超过历史最高则重新生成
            if ($this->mInstanceInfo['module'] == 'Instance') {
                $comboList = $this->getInstanceComboList();
                if (!isset($comboList[$this->mInstanceInfoConfig['index']]) || $comboList[$this->mInstanceInfoConfig['index']]['combo'] < $_POST['combo']) {
                    $comboList = D('GInstance')->maxComboList();
                    $jsonData = json_encode($comboList);
                    D('Predis')->cli('game')->set('instance_combo', $jsonData);
                }
            }

        }

        //获取副本难度
        if ($this->mInstanceInfo['module'] != 'Instance') {
            $difficulty = 0;
        } else {
            $difficulty = $this->getInstanceDiff($this->mInstanceInfoConfig['index']);
        }

        //记录日志
        $log['tid'] = $this->mTid;
        $log['module'] = $this->mInstanceInfo['module'];
        $log['instance'] = $this->mInstanceInfoConfig['index'];
        $log['group'] = $this->mInstanceInfoConfig['group'];
        $log['difficulty'] = $difficulty;
        $log['partner'] = field2string($this->mInstanceInfo['partner']);
        $log['drop'] = json_encode($this->mDropItemList);
//        $log['damage'] = $_POST['damage'] > 0 ? $_POST['damage'] : 0;
        $log['result'] = $result;
        $log['starttime'] = $this->mInstanceInfo['ctime'];
        $log['is_sweep'] = 0;
        D('LInstance')->CreateData($log);

        //清除当前玩家未结束的所有副本
        D('Predis')->cli('fight')->del('ins:t:' . $this->mTid);
        D('Predis')->cli('fight')->del('dyn:t:' . $this->mTid);

        return true;

    }

    //获取连击列表
    protected function getInstanceComboList()
    {
        //获取副本最大combo信息
        $json = D('Predis')->cli('game')->get('instance_combo');

        //如果redis没有则重新生成
        if (empty($json)) {
            $comboList = D('GInstance')->maxComboList();
            $jsonData = json_encode($comboList);
            D('Predis')->cli('game')->set('instance_combo', $jsonData);
        } else {
            $comboList = json_decode($json, true);
        }

        if (empty($comboList)) {
            return array();
        }
        return $comboList;
    }

    /******************************************************** 副本 Instance 结束 ********************************************************/

    /******************************************************** 副本 Dynamic 结束 ********************************************************/

    //验证副本是否可以开始
    private function dynamicCheck($index)
    {

        //查询副本配置
        $this->mBattleConfig = D('static')->access('dynamic_info', $index);
        if (empty($this->mBattleConfig)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //查询体力是否足够
        if (!$this->mValityNow = $this->verify($this->mBattleConfig['need_vality'], 'vality')) {
            return false;
        }

        return true;
    }

    //开始副本
    protected function dynamicFight($index, $module, $partner, $target)
    {

        //清除副本
        $this->cleanFight();

        //检查是否可以开始
        if (!$this->dynamicCheck($index)) {
            return false;
        }

        //获取战斗信息
        $return['player'] = $this->getFightInfo($this->mTid, $partner);

        //检查对手信息是否已经存在
        if (isset($target['info'])) {
            $opponent = $target['info'];
        } else if (isset($target['partner'])) {
            $opponent = $this->getFightInfo($target['tid'], $target['partner']);
        } else {

            //检查对手是否还在原来的名次
            if (!empty($target['tid'])) {
                $where['tid'] = $target['tid'];
            }
            if (!empty($target['rank'])) {
                $where['rank'] = $target['rank'];
            }
            $arenaTargetInfo = D('GArena')->getRowCondition($where);
            if (empty($arenaTargetInfo)) {
                C('G_ERROR', 'arena_data_need_to_update');
                return false;
            }

            //获取对手详细信息
            $opponent = $this->getArenaDefense($arenaTargetInfo);

        }

        //创建挑战记录
        $battle['index'] = $index;
        $battle['module'] = $module;
        $battle['partner'] = json_encode($partner);
        $battle['target_tid'] = $target['tid'];
        $battle['target_team'] = json_encode($opponent);
        $battle['ctime'] = time();
        D('Predis')->cli('fight')->hmset('dyn:t:' . $this->mTid, $battle);
        D('Predis')->cli('fight')->expire('dyn:t:' . $this->mTid, get_config('REDIS_TOKEN_TIME'));

        //对手信息
        $return['target'] = $opponent;

        //返回
        return $return;

    }

    //副本胜利
    protected function dynamicWin($index)
    {

        //检查是否可以开始
        if (!$this->dynamicCheck($index)) {
            return false;
        }

        //战斗结果
        $result = 1;

        //查询副本开始记录
        $this->mBattleInfo = D('Predis')->cli('fight')->hgetall('dyn:t:' . $this->mTid);
        if (empty($this->mBattleInfo)) {
            C('G_ERROR', 'dynamic_error');
            return false;
        }

        //副本是否正确
        if ($index != $this->mBattleInfo['index']) {
            C('G_ERROR', 'dynamic_error');
            return false;
        }

        //开始事务
        $this->transBegin();

        //结算
        if (!$this->dynamicEnd($result)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['result'] = $result;
        $return['target'] = $this->mBattleInfo['target_tid'];
        return $return;

    }

    //副本失败
    protected function dynamicLose($index)
    {

        //检查是否可以开始
        if (!$this->dynamicCheck($index)) {
            return false;
        }

        //战斗结果
        $result = 0;

        //查询副本开始记录
        $this->mBattleInfo = D('Predis')->cli('fight')->hgetall('dyn:t:' . $this->mTid);
        if (empty($this->mBattleInfo)) {
            C('G_ERROR', 'dynamic_error');
            return false;
        }

        //副本是否正确
        if ($index != $this->mBattleInfo['index']) {
            C('G_ERROR', 'dynamic_error');
            return false;
        }

        //开始事务
        $this->transBegin();

        //结束副本
        if (!$this->dynamicEnd($result)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['result'] = $result;
        $return['target'] = $this->mBattleInfo['target_tid'];
        return $return;

    }

    private function dynamicEnd($result)
    {
        //验证战斗时间
        if(!$this->verifyDuration($_POST['duration'], $this->mBattleInfo['ctime'])){
            $result = -1;
        }

        //扣除体力
        if (!$this->recover('vality', $this->mBattleConfig['need_vality'])) {
            return false;
        }

        if ($result == 1) {

            //加钱
            if (!$this->produce('gold', $this->mBattleConfig['bonus_gold'])) {
                return false;
            }

            //加经验
            if (!$this->produce('teamExp', $this->mBattleConfig['bonus_team_exp'])) {
                return false;
            }

            //战队经验
            foreach ($this->mBattleInfo['partner'] as $value) {
                if (!$this->produce('partnerExp', $value['group'], $this->mBattleConfig['bonus_partner_exp'])) {
                    return false;
                }
            }

            //记录当日副本最高combo
//            D('TDailyCount')->record($this->mTid, 6, $_POST['combo']);

            //记录成就
//            D('GCount')->combo($this->mTid, $_POST['combo']);

        }

        //记录日志
        $log['tid'] = $this->mTid;
        $log['dynamic'] = $this->mBattleConfig['index'];
        $log['module'] = $this->mBattleInfo['module'];
        $log['partner'] = field2string($this->mBattleInfo['partner']);
        $log['target_tid'] = $this->mBattleInfo['target_tid'];
        $log['target_team'] = $this->mBattleInfo['target_team'];
        $log['result'] = $result;
        $log['starttime'] = $this->mBattleInfo['ctime'];
        D('LDynamic')->CreateData($log);

        //清除当前玩家未结束的所有副本
        D('Predis')->cli('fight')->del('ins:t:' . $this->mTid);
        D('Predis')->cli('fight')->del('dyn:t:' . $this->mTid);

        return true;

    }
    /******************************************************** 副本 Dynamic 结束 ********************************************************/

    //验证战斗时间
    private function verifyDuration($duration, $ctime)
    {
        //战斗是否加速
        $serverTime = time() - $ctime;
        if (($serverTime - $duration) < -1) {
            D('LCheat')->cLog($this->mTid, 101, $_POST['duration'], $serverTime);
            //踢下线
            D('Predis')->cli('game')->del('u:' . $this->mUid);
            D('Predis')->cli('game')->del('t:' . $this->mToken);
            D('Predis')->cli('game')->del($this->mSessionKey);
            return false;
        }

        return true;
    }

}