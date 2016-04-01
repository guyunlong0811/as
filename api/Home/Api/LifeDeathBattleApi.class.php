<?php
namespace Home\Api;

use Think\Controller;

class LifeDeathBattleApi extends BEventApi
{

    const GROUP = 14;
    private $mLifeDeathBattleInfo;
    private $mLifeDeathBattleConfig;
    private $mDynamicConfig;

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) exit;
        $this->mLifeDeathBattleInfo = D('GLifeDeathBattle')->getRow($this->mTid);
        if (empty($this->mLifeDeathBattleInfo)) {
            $this->open();
            $this->mLifeDeathBattleInfo = D('GLifeDeathBattle')->getRow($this->mTid);
        } else {
            $this->mLifeDeathBattleConfig = D('Static')->access('life_death_battle', $this->mLifeDeathBattleInfo['floor']);
        }
        $this->mDynamicConfig = D('Static')->access('dynamic_info', $this->mLifeDeathBattleConfig['dynamic_info']);
        return;
    }

    //开始生死门
    private function open()
    {
        $this->mLifeDeathBattleConfig = D('Static')->access('life_death_battle', 1);
        $opponent = $this->getOpponent(1);
        $next = $this->getNextReward(1);
        D('GLifeDeathBattle')->open($this->mTid, $opponent, $next);
        $this->mLifeDeathBattleInfo = D('GLifeDeathBattle')->getRow($this->mTid);
        $this->useEventCount();//使用重置次数
        return true;
    }

    //重置生死门
    private function reset()
    {
        if ($this->mEventRemainCount <= 0) {
            return false;
        }
        $where['tid'] = $this->mTid;
        $update['floor'] = 1;
        $update['opponent'] = $this->getOpponent($update['floor']);
        $update['reward'] = '[]';
        $update['reward_last'] = '[]';
        $update['reward_next'] = $this->getNextReward($update['floor']);
        $update['status'] = 0;
        //修改数据
        if (false === D('GLifeDeathBattle')->UpdateData($update, $where)) {
            return false;
        }
        $this->useEventCount();//使用重置次数
        $this->mLifeDeathBattleInfo = D('GLifeDeathBattle')->getRow($this->mTid);
        return true;
    }

    //获取对手信息
    private function getOpponent($floor)
    {

        //获取竞技场最大排名
        $maxRank = M('GArena')->max('rank');

        //获取当前自己的排名
        $myRank = D('GArena')->getAttr($this->mTid, 'rank');

        //获取没有竞技场排名，开启竞技场
        if (empty($myRank)) {
            D('GArena')->open($this->mTid);
            $myRank++;
        }

        //获取小队战力
        $myForceTop = D('GCount')->getAttr($this->mTid, 'force_top');

        //随机对手
        $luaRs = lua('life_death_battle', 'life_death_battle', array((int)$myRank, (int)$myForceTop, (int)$floor, (int)$maxRank,));

        //战力算法
        $robot = ceil($luaRs[1]);
        $where = array();
        if ($robot > 0) {
            $robotConfig = D('Static')->access('arena_robot', $robot);
            $rand = rand($robotConfig['high'], $robotConfig['low']);
            $where['tid'] = $rand;
        } else {
            //排名算法
            $rank = ceil($luaRs[0]);
            if ($rank > $maxRank) {
                $rank = $maxRank;
            }
            if ($myRank == $rank) {
                if ($rank > 1) {
                    $rank = $rank - 1;
                } else {
                    $rank = 2;
                }
            }
            $where['rank'] = $rank;
        }

        //获取竞技场信息
        $arenaTargetInfo = D('GArena')->getRowCondition($where);

        //获取对手详细信息
        $opponent = $this->getArenaDefense($arenaTargetInfo);

        //整理数据
        $opponent['tid'] = $arenaTargetInfo['tid'];
        $opponent['rank'] = $rank;
        $teamInfo = D('GTeam')->getRow($arenaTargetInfo['tid'], array('nickname', 'icon', 'level'));
        $opponent = $opponent + $teamInfo;
        return json_encode($opponent);

    }

    //获取下一层奖励
    private function getNextReward($floor)
    {
        $box = D('Static')->access('life_death_battle', $floor, 'box');
        $list = D('SBox')->open1($box);
        return json_encode($list);
    }

    //计算奖金
    private function getBonusGold()
    {
        $floor = $this->mLifeDeathBattleInfo['floor'];
        $status = $this->mLifeDeathBattleInfo['status'];
        //计算奖金
        if ($status == '0' || $status == '3') {
            $gold = 0;
        } else {
            $lifeDeathAllConfig = D('Static')->access('life_death_battle');
            $gold = 0;
            if ($status == '1') {
                foreach ($lifeDeathAllConfig as $value) {
                    $gold += $value['gold'];
                }
            } else if ($status == '2' || $status == '-1') {
                foreach ($lifeDeathAllConfig as $value) {
                    if ($value['index'] < $floor) {
                        $gold += $value['gold'];
                    }
                }
            }
        }
        return $gold;
    }

    //获取当前楼层信息
    public function getInfo()
    {

        //如果正在打则直接判负
//        $battleInfo = D('Predis')->cli('fight')->hgetall('dyn:t:' . $this->mTid);
//        if (!empty($battleInfo) && $battleInfo['module'] == 'LifeDeathBattle') {
//            $_POST['combo'] = 0;//连击默认为0
//            $this->lose();//战败
//            $this->mLifeDeathBattleInfo = D('GLifeDeathBattle')->getRow($this->mTid);//重新获取数据
//        }

        //检查是否需要重置
        $status = $this->mLifeDeathBattleInfo['status'];//当前状态
        if ($status == '3') {//重置
            $this->reset();
        }

        //今日剩余重置次数
        $return['remain'] = $this->mEventRemainCount;

        //返回数据
//        if($this->mLifeDeathBattleInfo['status'] == 3){
//            $return['floor'] = 0;//当前进度
//            $return['gold'] = 0;//获取当前奖金
//            $return['opponent'] = array();//对手信息
//            $return['reward'] = array();//总奖励
//            $return['reward_last'] = array();//上一关奖励
//            $return['reward_next'] = array();//下一关奖励
//        }else{
        $return['floor'] = $this->mLifeDeathBattleInfo['floor'];//当前进度
        $return['opponent'] = json_decode($this->mLifeDeathBattleInfo['opponent'], true);//对手信息
        $reward = json_decode($this->mLifeDeathBattleInfo['reward'], true);//总奖励
        $rewardList = array();
        foreach ($reward as $key => $value) {
            foreach ($value as $k => $val) {
                $arr['type'] = $key;
                $arr['id'] = $k;
                $arr['count'] = $val;
                $rewardList[] = $arr;
            }
        }
        $return['reward'] = $rewardList;
        $return['reward_last'] = json_decode($this->mLifeDeathBattleInfo['reward_last'], true);//上一关奖励
        $return['reward_next'] = json_decode($this->mLifeDeathBattleInfo['reward_next'], true);//下一关奖励

//        }
        $return['max'] = $this->mLifeDeathBattleInfo['max'];//最佳战绩
        $return['status'] = $this->mLifeDeathBattleInfo['status'];//当前状态
        return $return;
    }

    //发起挑战
    public function fight()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '0' && $this->mLifeDeathBattleInfo['status'] != '2') {
            C('G_ERROR', 'life_death_cannot_fight');
            return false;
        }

        //实例化PVP
        $target['info'] = json_decode($this->mLifeDeathBattleInfo['opponent'], true);
        $target['tid'] = $target['info']['tid'];
        unset($target['info']['tid']);
        unset($target['info']['rank']);
        unset($target['info']['nickname']);
        $dynId = $this->mLifeDeathBattleConfig['dynamic_info'];
        if (!$return = $this->dynamicFight($dynId, 'LifeDeathBattle', $_POST['partner'], $target)) {
            return false;
        }

        //返回
        return $return;

    }

    //战斗胜利
    public function win()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '0' && $this->mLifeDeathBattleInfo['status'] != '2') {
            C('G_ERROR', 'life_death_cannot_fight');
            return false;
        }

        //副本胜利
        $dynId = $this->mLifeDeathBattleConfig['dynamic_info'];
        $ret = $this->dynamicWin($dynId);
        if (!$ret) {
            return false;
        }
        $result = $ret['result'];

        //开始事务
        $this->transBegin();

        if (!$this->end($result)) {
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

    //战斗失败
    public function lose()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '0' && $this->mLifeDeathBattleInfo['status'] != '2') {
            C('G_ERROR', 'life_death_cannot_fight');
            return false;
        }

        $result = 0;

        //战斗失败
        $dynId = $this->mLifeDeathBattleConfig['dynamic_info'];
        if (false === $this->dynamicLose($dynId)) {
            return false;
        }

        //开始事务
        $this->transBegin();

        if (!$this->end($result)) {
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

    //结束
    private function end($result)
    {

        $where['tid'] = $this->mTid;

        switch ($result) {

            //战斗胜利
            case 1:

                //是否刷新最大层数
                if ($this->mLifeDeathBattleInfo['floor'] > $this->mLifeDeathBattleInfo['max']) {
                    $update['max'] = $this->mLifeDeathBattleInfo['floor'];
                }

                //将奖励放入奖池
                $update['reward_last'] = $this->mLifeDeathBattleInfo['reward_next'];
                $rewardList = json_decode($this->mLifeDeathBattleInfo['reward'], true);
                $nextRewardList = json_decode($this->mLifeDeathBattleInfo['reward_next'], true);
                foreach ($nextRewardList as $value) {
                    if (isset($rewardList[$value['type']][$value['id']])) {
                        $rewardList[$value['type']][$value['id']] += $value['count'];
                    } else {
                        $rewardList[$value['type']][$value['id']] = $value['count'];
                    }
                }

                $update['reward'] = json_encode($rewardList);

                //判断是否已经通关
                $lifeDeathAllConfig = D('Static')->access('life_death_battle');
                $nextFloor = $this->mLifeDeathBattleInfo['floor'] + 1;
                if (!isset($lifeDeathAllConfig[$nextFloor])) {
                    $update['reward_next'] = '[]';
                    $update['status'] = 1;
                } else {
                    $update['floor'] = $nextFloor;
                    $update['opponent'] = $this->getOpponent($update['floor']);
                    $update['reward_next'] = $this->getNextReward($update['floor']);
                    $update['status'] = 2;
                }

                break;

            //战斗失败
            case 0:
            case -1:
                //清空奖励
                $update['reward_last'] = '[]';
                $update['reward_next'] = '[]';
                $update['status'] = -1;
                break;

        }

        //修改数据
        if (false === D('GLifeDeathBattle')->UpdateData($update, $where)) {
            return false;
        }
        return true;
    }

    //放弃
    public function giveUp()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '2') {
            C('G_ERROR', 'life_death_cannot_give_up');
            return false;
        }

        //获取奖金值
        $gold = $this->getBonusGold();

        //获取百分比
        $rate = D('Static')->access('params', 'LIFE_DEATH_BATTLE_GOLD');

        //实际值
        $gold = round($gold * $rate / 100);

        //开始事务
        $this->transBegin();

        //加钱
        if (!$this->produce('gold', $gold)) {
            goto end;
        }//金币

        //加物品
        if (!$this->reward()) {
            goto end;
        }

        //重置
        if (!$this->reset()) {
            if (false === D('GLifeDeathBattle')->status($this->mTid, 3)) {
                goto end;
            }
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

    //宝石买奖品
    public function buy()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '-1') {
            C('G_ERROR', 'life_death_not_fail_to_buy');
            return false;
        }

        //一层都没有打过
        if ($this->mLifeDeathBattleInfo['floor'] == '1') {
            C('G_ERROR', 'life_death_no_bonus');
            return false;
        }

        //获取需要消耗的水晶
        $need_diamond = D('Static')->access('life_death_battle', $this->mLifeDeathBattleInfo['floor'], 'lose_buy');

        //检查水晶
        if (!$diamond = $this->verify($need_diamond, 'diamond')) {
            return false;
        }

        //获取奖金值
        $gold = $this->getBonusGold();

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover('diamond', $need_diamond, null, $diamond)) {
            goto end;
        }

        //加钱
        if (!$this->produce('gold', $gold)) {
            goto end;
        }//金币

        //加物品
        if (!$this->reward()) {
            goto end;
        }

        //重置
        if (!$this->reset()) {
            if (false === D('GLifeDeathBattle')->status($this->mTid, 3)) {
                goto end;
            }
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

    //获取通关奖励
    public function clear()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '1') {
            C('G_ERROR', 'life_death_not_clear');
            return false;
        }

        //获取奖金值
        $gold = $this->getBonusGold();

        //开始事务
        $this->transBegin();

        //加钱
        if (!$this->produce('gold', $gold)) {
            goto end;
        }

        //加物品
        if (!$this->reward()) {
            goto end;
        }

        //重置
        if (!$this->reset()) {
            if (false === D('GLifeDeathBattle')->status($this->mTid, 3)) {
                goto end;
            }
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

    //重新开始
    public function restart()
    {

        //查看状态
        if ($this->mLifeDeathBattleInfo['status'] != '-1') {
            C('G_ERROR', 'life_death_not_fail_to_restart');
            return false;
        }

        //重置
        if (!$this->reset()) {
            C('G_ERROR', 'life_death_reset_today');
            return false;
        }

        //返回
        return true;
    }

    //发放奖池奖励
    private function reward()
    {
        //加物品
        $rewardList = json_decode($this->mLifeDeathBattleInfo['reward'], true);
        foreach ($rewardList as $key => $value) {
            foreach ($value as $k => $val) {
                if (!$this->produce($this->mBonusType[$key], $k, $val)) {
                    return false;
                }

                //如果有开出伙伴
                if ($key == '3') {

                    //获取该伙伴配置
                    $partnerGroupConfig = D('Static')->access('partner_group', $k);
                    foreach ($partnerGroupConfig as $value) {
                        if ($value['is_init'] == 1) {
                            $partnerConfig = $value;
                            break;
                        }
                    }

                    //查看伙伴是否是SS级
                    if ($partnerConfig['partner_class'] == '9') {
                        //发送公告
                        $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
                        $params['partner_name'] = $partnerConfig['name'];
                        $noticeConfig = D('SEventString')->getConfig('LIFE_DEATH_BATTLE_PARTNER', $params);
                        D('GChat')->sendNoticeMsg($this->mTid, $noticeConfig['des'], $noticeConfig['show_level']);
                    }

                }

            }
        }

        return true;
    }

}