<?php
namespace Home\Api;

use Think\Controller;

class LeagueQuestApi extends BBattleApi
{

    const COUNT = 4;//同时获得公会任务数量quest
    private $leagueTeamInfo;

    public function _initialize()
    {

        parent::_initialize();
        //验证是否已经加入公会
        $leagueTeamInfo = D('GLeagueTeam')->getRow($this->mTid);
        if (empty($leagueTeamInfo)) {
            C('G_ERROR', 'league_not_attended');
            exit;
        }
        $this->leagueTeamInfo = $leagueTeamInfo;

    }

    //获取所有的任务列表
    public function getList()
    {

        $questList = json_decode($this->leagueTeamInfo['quest_list'], true);
        $count = count($questList);
        if ($count != self::COUNT) {
            for ($i = 1; $i <= (self::COUNT - $count); ++$i) {
                $questList[] = $this->getQuestId($questList);
            }
            $where['tid'] = $this->mTid;
            sort($questList);
            $data['quest_list'] = json_encode($questList);
            D('GLeagueTeam')->UpdateData($data, $where);
        }

        //返回
        return $questList;

    }

    //获取单个任务ID
    private function getQuestId($list)
    {
        $questConfig = D('Static')->access('league_quest');
        foreach ($questConfig as $key => $value) {
            if (!in_array($key, $list)) {
                $rate[$key] = $value['probability'];
            }
        }
        //计算权重，返回结果
        return weight($rate);
    }

    //发起挑战
    public function fight()
    {

        //检查挑战次数是否足够
        $total = D('Static')->access('params', 'league_quest_count');
        $use = D('TDailyCount')->getCount($this->mTid, 5);
        if ($total - $use <= 0) {
            C('G_ERROR', 'challenges_not_enough');
            return false;
        }

        //任务是否在列表中
        $questList = json_decode($this->leagueTeamInfo['quest_list'], true);
        if (!in_array($_POST['quest_id'], $questList)) {
            C('G_ERROR', 'league_quest_not_in_list');
            return false;
        }

        //随机对手
        $level = D('GTeam')->getAttr($this->mTid, 'level');
        $type = D('Static')->access('league_quest', $_POST['quest_id'], 'type');
        $maxRank = M('GArena')->max('rank');
        $rank = lua('league_quest_battle', 'league_quest_combat', array($level, $type, $maxRank,));

        //实例化PVP
        $dynId = D('Static')->access('league_quest', $_POST['quest_id'], 'dynamic_info');
        $target['rank'] = $rank;
        if (!$opponent = $this->dynamicFight($dynId, 'LeagueQuest', $_POST['partner'], $target)) {
            return false;
        }

        //返回
        $return = D('GTeam')->getFightInfo($this->mTid);
        $return['target'] = $opponent;
        return $return;

    }

    //战斗胜利
    public function win()
    {

        //副本胜利
        $dynId = D('Static')->access('league_quest', $_POST['quest_id'], 'dynamic_info');
        $ret = $this->dynamicWin($dynId);
        if (!$ret) {
            return false;
        }
        $result = $ret['result'];

        //开始事务
        $this->transBegin();

        if ($result == 1) {

            //发放奖励
            $questConfig = D('Static')->access('league_quest', $_POST['quest_id']);
            if (!$this->bonus($questConfig)) {
                goto end;
            }

            //扣除挑战次数
            if (!D('TDailyCount')->record($this->mTid, 5)) {
                goto end;
            }

            //精英副本发送全公会邮件
            if ($questConfig['type'] == 2) {

                $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
                $params['leaguename'] = D('GLeague')->getAttr($this->leagueTeamInfo['league_id'], 'name');
                $members = D('GLeagueTeam')->getALL($this->leagueTeamInfo['league_id']);
                //获取公会所有的玩家
                foreach ($members as $value) {
                    $mail_id = $questConfig['bonus_mail'];
                    D('GMail')->send($mail_id, $this->mTid, $value['tid'], $params);
                }

            }

        } else {
            C('G_ERROR', 'battle_anomaly');
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        if ($result == 1) {
            return true;
        } else {
            return false;
        }

    }

    //战斗失败
    public function lose()
    {
        //战斗失败
        $dynId = D('Static')->access('league_quest', $_POST['quest_id'], 'dynamic_info');
        if (false === $this->dynamicLose($dynId)) {
            return false;
        }
        return true;
    }

}