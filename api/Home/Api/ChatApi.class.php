<?php
namespace Home\Api;

use Think\Controller;

class ChatApi extends BaseApi
{

    const WORLD_CHAT = 1;

    //发送私聊消息
    public function sendPrivateMsg()
    {
        //查询是否被禁言
        if ($this->mSilence == 1) {
            C('G_ERROR', 'user_silence');
            return false;
        }
        //查询是否发送的是空消息
        if (empty($_POST['msg'])) {
            C('G_ERROR', 'chat_msg_no_empty');
            return false;
        }
        //自己不能和自己发消息
        if ($_POST['target_tid'] == $this->mTid) {
            C('G_ERROR', 'chat_with_self');
            return false;
        }
        //查询好友关系
        $friend = D('GFriend')->getRow($this->mTid, $_POST['target_tid']);
        if (empty($friend)) {
            C('G_ERROR', 'friend_not_yet');
            return false;
        }
        //查询是否存在接收消息角色
        if (!D('GTeam')->isExist($_POST['target_tid'])) {
            C('G_ERROR', 'error_team');
            return false;
        }
        //发送消息
        return D('GChat')->sendPrivateMsg($this->mTid, $_POST['target_tid'], $_POST['msg']);
    }

    //接收私聊列表
    public function getPrivateList()
    {
        return D('GChat')->getPrivateList($this->mTid);
    }

    //查看单个玩家私聊信息
    public function getPrivateMsg()
    {
        return D('GChat')->getPrivateMsg($_POST['target_tid'], $this->mTid);
    }

    //世界频道发言
    public function sendWorldMsg()
    {
        //查询是否被禁言
        if ($this->mSilence == 1) {
            C('G_ERROR', 'user_silence');
            return false;
        }
        //查询是否发送的是空消息
        if (empty($_POST['msg'])) {
            C('G_ERROR', 'chat_msg_no_empty');
            return false;
        }
        //查询是否到了发言的最低等级
        $level = D('GTeam')->getAttr($this->mTid, 'level');
        $minLevel = D('Static')->access('params', 'CHAT_LEVEL_MIN');
        if ($level < $minLevel) {
            C('G_ERROR', 'team_level_low');
            return false;
        }
        //查询今天是否还有发送资格
        $count = D('Static')->access('params', 'CHAT_MESSAGE_MAX');
        $usedCount = D('TDailyCount')->getCount($this->mTid, self::WORLD_CHAT);
        if ($count <= $usedCount) {
            C('G_ERROR', 'chat_count_not_enough');
            return false;
        }
        //减少一次
        D('TDailyCount')->record($this->mTid, self::WORLD_CHAT);

        //发送消息
        $info = D('GTeam')->field('nickname,icon,level')->where("`tid`='{$this->mTid}'")->find();
        return D('GChat')->sendWorldMsg($this->mTid, $_POST['msg'], $info);
    }

    //查看世界频道最近聊天情况
    public function getWorldMsg()
    {
        //查询消息
        return D('GChat')->getWorldMsg($_POST['last']);
    }

    //公会频道发言
    public function sendLeagueMsg()
    {
        //查询是否被禁言
        if ($this->mSilence == 1) {
            C('G_ERROR', 'user_silence');
            return false;
        }
        //查询是否发送的是空消息
        if (empty($_POST['msg'])) {
            C('G_ERROR', 'chat_msg_no_empty');
            return false;
        }
        //查询发言人的公会ID
        $where['tid'] = $this->mTid;
        $leagueId = D('GLeagueTeam')->where($where)->getField('league_id');
        if (!$leagueId) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }
        //查询玩家等级&icon
        $info = D('GTeam')->field('nickname,icon,level')->where("`tid`='{$this->mTid}'")->find();
        //发送消息
        return D('GChat')->sendLeagueMsg($this->mTid, $leagueId, $_POST['msg'], $info);
    }

    //查看公会频道最近聊天情况
    public function getLeagueMsg()
    {
        //查询玩家的公会ID
        $where['tid'] = $this->mTid;
        $leagueId = D('GLeagueTeam')->where($where)->getField('league_id');
        if (!$leagueId) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }
        //查询消息
        return D('GChat')->getLeagueMsg($leagueId, $_POST['last']);
    }

    //查看系统公告最近聊天情况
    public function getNoticeMsg()
    {
        //获取当前世界ID
        $keys = D('Predis')->cli('social')->keys('w:*');
        $return['world']['id'] = D('Predis')->cli('social')->get('w:id');
        $return['world']['count'] = count($keys) - 1;

        //获取当前公会ID
        $return['league']['id'] = $this->mSessionInfo['league_id'] == 0 ? 0 : D('Predis')->cli('social')->get('l:' . $this->mSessionInfo['league_id'] . ':id');
        $keys = D('Predis')->cli('social')->keys('l:' . $this->mSessionInfo['league_id'] . ':*');
        $return['league']['count'] = count($keys) - 1;

        //查询消息
        $return['list'] = D('GChat')->getNoticeMsg($_POST['last']);

        return $return;
    }

}