<?php
namespace Home\Api;

use Think\Controller;

class LeagueArenaApi extends BEventApi
{

    const GROUP = 22;
    private $mNow;//当前时间
    private $mIsFight;//是否开战
    private $mIsMatch;//是否匹配
    private $mMatchTime;//匹配时间
    private $mFightStartTime;//开战时间
    private $mFightEndTime;//结束时间
    private $mLeagueId;//公会ID
    private $mAreaId;//区域ID

    public function _initialize()
    {
        parent::_initialize();

        //活动限制
        $this->mNow = time();
        $this->mIsFight = $this->event(self::GROUP);
        $this->mIsMatch = D('Predis')->cli('fight')->get('laf:match');

        $this->mMatchTime = $this->mEventInfo['starttime'] - 3600;
        $this->mFightStartTime = $this->mEventInfo['starttime'];
        $this->mFightEndTime = $this->mEventInfo['endtime'];

        //查询玩家是否已经参加公会
        $this->mLeagueId = $this->mSessionInfo['league_id'];
        if (empty($this->mLeagueId)) {
            C('G_ERROR', 'league_not_attended');
            exit;
        }

        //查询公会所属区域
        $this->mAreaId = D('GLeagueArena')->getAreaId($this->mLeagueId);

        return true;
    }

    //获取活动信息
    public function getInfo()
    {

        //查询公会报名数
        $return['count'] = D('GLeagueArena')->getRegCount($_POST['area']);

        //获取公会信息
        $leagueArenaInfo = D('GLeagueArena')->getRow($this->mLeagueId);
        if ($leagueArenaInfo['area'] == 0) {
            $return['rank'] = 0;
            $return['point'] = 0;
        } else {
            //查询公会排名
            $return['rank'] = D('GLeagueArena')->getRank($leagueArenaInfo['area'], $leagueArenaInfo['point'], $leagueArenaInfo['utime']);
            $return['rank'] = $leagueArenaInfo['point'] > 0 ? $return['rank'] : 0;

            //查询公会积分
            $return['point'] = $leagueArenaInfo['point'];
        }

        //查询区域排名信息
        $list = D('GLeagueArenaRank')->getList($_POST['area']);
        $return['list'] = empty($list) ? array() : $list;
        return $return;
    }

    //获取公会队伍信息
    public function getTeamList()
    {

        //是否已经匹配
        $return['match'] = $this->mIsMatch ? 1 : 0;

        //获取公会所有队伍
        $where['league_id'] = $this->mLeagueId;
        $teamList = D('GLeagueArenaTeam')->field(array('id', 'tid', 'partner', 'opponent', 'status'))->where($where)->select();
        $list = array();

        //获取这些队伍的具体信息
        if (!empty($teamList)) {

            //查询条件&调整数据
            $tidArr = array();
            foreach ($teamList as $key => $value) {
                $tidArr[] = $value['tid'];
                $teamList[$key]['partner'] = json_decode($value['partner'], true);
            }

            //查询信息
            $wherePartner['tid'] = array('in', $tidArr);
            $select = D('GPartner')->field(array('tid', 'group', 'index', 'level', 'favour', 'force'))->where($wherePartner)->select();
            $partnerList = array();
            foreach ($select as $value) {
                $partnerList[$value['tid']][$value['group']] = $value;
            }

            //整理数据
            foreach ($teamList as $key => $value) {
                $arrRow['id'] = $value['id'];
                $arrRow['tid'] = $value['tid'];
                $arrRow['partner'] = array();
                foreach ($value['partner'] as $val) {
                    $arrPartner['group'] = $val;
                    $arrPartner['index'] = $partnerList[$value['tid']][$val]['index'];
                    $arrPartner['level'] = $partnerList[$value['tid']][$val]['level'];
                    $arrPartner['favour'] = $partnerList[$value['tid']][$val]['favour'];
                    $arrPartner['force'] = $partnerList[$value['tid']][$val]['force'];
                    $arrRow['partner'][] = $arrPartner;
                }
                $arrRow['opponent'] = $value['opponent'];
                $arrRow['status'] = $value['status'];
                $list[] = $arrRow;
            }

        }

        //返回
        $return['list'] = $list;
        return $return;
    }

    //队伍上阵
    public function register()
    {

        //上阵情况
        if (empty($_POST['partner'])) {
            C('G_ERROR', 'partner_empty');
            return false;
        }

        //查看公会是否已经报名
        if (false === D('GLeagueArena')->isReg($this->mLeagueId)) {
            C('G_ERROR', 'league_not_reg');
            return false;
        }

        //获取玩家已报名的伙伴
        $partnerReg = D('GLeagueArenaTeam')->getPartners($this->mTid);

        //查看是否有重复伙伴
        $repeat = array_intersect($partnerReg, $_POST['partner']);
        if (!empty($repeat)) {
            C('G_ERROR', 'league_arena_partner_repeat');
            return false;
        }

        //写入数据
        $add['tid'] = $this->mTid;
        $add['league_id'] = $this->mLeagueId;
        $add['partner'] = json_encode($_POST['partner']);
        if (false === $id = D('GLeagueArenaTeam')->CreateData($add)) {
            return false;
        }
        return $id;
    }

    //队伍下阵
    public function ban()
    {
        //活动限制
        if ($this->mMatchTime <= $this->mNow && $this->mNow <= $this->mFightEndTime) {
            C('G_ERROR', 'league_arena_not_allow_change');
            return false;
        }

        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //查询战役信息
        $battleInfo = D('GLeagueArenaTeam')->getRow($_POST['battle_id']);

        //检查是否是自己公会的队伍
        if ($battleInfo['league_id'] != $this->mLeagueId) {
            C('G_ERROR', 'league_arena_battle_error');
            return false;
        }

        //下阵
        $where['id'] = $_POST['battle_id'];
        if (false === D('GLeagueArenaTeam')->DeleteData($where)) {
            return false;
        }
        return true;
    }

    //队伍调整
    public function change()
    {

        $now = time();

        //查询战役信息
        $battleInfo = D('GLeagueArenaTeam')->getRow($_POST['battle_id']);

        //调整限制
        if ($this->mMatchTime <= $now && $now <= $this->mFightEndTime) {
            C('G_ERROR', 'league_arena_not_allow_change');
            return false;
        }

        //信息错误
        if ($battleInfo['tid'] != $this->mTid) {
            C('G_ERROR', 'league_arena_battle_error');
            return false;
        }

        //下阵
        if (empty($_POST['partner'])) {
            $where['id'] = $_POST['battle_id'];
            $where['tid'] = $this->mTid;
            if (false === D('GLeagueArenaTeam')->DeleteData($where)) {
                return false;
            }
        } else {//调整

            //获取玩家已报名的伙伴
            $partnerReg = D('GLeagueArenaTeam')->getPartners($this->mTid, $_POST['battle_id']);

            //查看是否有重复伙伴
            $repeat = array_intersect($partnerReg, $_POST['partner']);
            if (!empty($repeat)) {
                C('G_ERROR', 'league_arena_partner_repeat');
                return false;
            }

            //写入数据
            $where['id'] = $_POST['battle_id'];
            $data['partner'] = json_encode($_POST['partner']);
            if (false === D('GLeagueArenaTeam')->UpdateData($data, $where)) {
                return false;
            }

        }

        //返回
        return true;
    }

    //发起挑战
    public function fight()
    {
        //活动限制
        if (!$this->mIsFight) {
            return false;
        }

        //查询战役信息
        $battleInfo = D('GLeagueArenaTeam')->getRow($_POST['battle_id']);

        //信息错误
        if ($battleInfo['status'] != '2') {
            if ($battleInfo['status'] == '3') {
                D('GLeagueArenaTeam')->changeStatus($_POST['battle_id'], 0);
            }
            C('G_ERROR', 'league_arena_battle_end');
            return false;
        }

        //信息错误
        if ($battleInfo['tid'] != $this->mTid) {
            C('G_ERROR', 'league_arena_battle_error');
            return false;
        }

        //信息错误
        if ($battleInfo['opponent'] == '0') {
            C('G_ERROR', 'league_arena_no_battle');
            return false;
        }

        //更改状态
        if (false === D('GLeagueArenaTeam')->changeStatus($_POST['battle_id'], 3)) {
            return false;
        }

        //查询对手信息
        $targetBattleInfo = D('GLeagueArenaTeam')->getRow($battleInfo['opponent']);

        //实例化PVP
        $dynId = D('Static')->access('league_pvp', $this->mAreaId, 'dynamic_info');
        $target['tid'] = $targetBattleInfo['tid'];
        $target['partner'] = json_decode($targetBattleInfo['partner'], true);
        if (!$return = $this->dynamicFight($dynId, 'LeagueArena', json_decode($battleInfo['partner'], true), $target)) {
            return false;
        }

        //返回
        D('Predis')->cli('fight')->hincrby('laf:fight', $this->mLeagueId, 1);
        D('GCount')->incAttr($this->mTid, 'league_arena');
        return $return;

    }

    //战斗胜利
    public function win()
    {

        //查询战役信息
        $battleInfo = D('GLeagueArenaTeam')->getRow($_POST['battle_id']);

        //信息错误
        if ($battleInfo['status'] != '3') {
            C('G_ERROR', 'league_arena_battle_end');
            return false;
        }

        //副本胜利
        $dynId = D('Static')->access('league_pvp', $this->mAreaId, 'dynamic_info');
        $ret = $this->dynamicWin($dynId);
        if (!$ret) {
            return false;
        }

        $result = $ret['result'];
        if ($result != 1) {
            C('G_BEHAVE', 'league_arena_lose');
            C('G_ERROR', 'battle_anomaly');
        }

        //开始事务
        $this->transBegin();

        //更改状态
        if (false === D('GLeagueArenaTeam')->changeStatus($_POST['battle_id'], 1)) {
            goto end;
        }

        //战斗结束
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
        if ($result == 1) {
            D('Predis')->cli('fight')->hincrby('laf:win', $this->mLeagueId, 1);
            D('GCount')->incAttr($this->mTid, 'league_arena_win');
            return true;
        } else {
            return false;
        }

    }

    //战斗失败
    public function lose()
    {

        //查询战役信息
        $battleInfo = D('GLeagueArenaTeam')->getRow($_POST['battle_id']);

        //信息错误
        if ($battleInfo['status'] != '3') {
            C('G_ERROR', 'league_arena_battle_end');
            return false;
        }

        //战斗失败
        $dynId = D('Static')->access('league_pvp', $this->mAreaId, 'dynamic_info');
        if (false === $ret = $this->dynamicLose($dynId)) {
            return false;
        }

        //返回信息
        $result = $ret['result'];

        //开始事务
        $this->transBegin();

        //更改状态
        if (false === D('GLeagueArenaTeam')->changeStatus($_POST['battle_id'], 0)) {
            goto end;
        }

        //战斗结束
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

    //战斗结算
    private function end($result)
    {

        //发放奖励
        $bonus = 0;
        switch ($result) {
            case '-1':
            case '0':
                $bonus = D('Static')->access('params', 'LEAGUE_PVP_LOSE_BONUS');//获得奖励
                break;
            case '1':
                $bonus = D('Static')->access('params', 'LEAGUE_PVP_WIN_BONUS');//获得奖励
                break;
        }
        //增加荣誉值
        if (false === $this->produce('contribution', $bonus)) {
            return false;
        }

        //返回
        return true;
    }

}