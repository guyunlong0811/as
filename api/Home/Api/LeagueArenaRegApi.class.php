<?php
namespace Home\Api;

use Think\Controller;

class LeagueArenaRegApi extends BEventApi
{

    const GROUP = 21;
    private $mLeagueId;//公会ID

    public function _initialize()
    {
        parent::_initialize();

        //查询玩家是否已经参加公会
        $this->mLeagueId = $this->mSessionInfo['league_id'];
        if (empty($this->mLeagueId)) {
            C('G_ERROR', 'league_not_attended');
            exit;
        }

        return true;
    }

    //获取公会报名信息
    public function getInfo()
    {
        $return = D('GLeagueArena')->getRow($this->mLeagueId);
        $return['fund'] = D('GLeague')->getAttr($this->mLeagueId, 'fund');
        return $return;
    }

    //公会报名
    public function register()
    {

        //活动限制
        if (!$this->event(self::GROUP)) {
            return false;
        }

        //检查权限
        if (false == $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //检查是否存在此区域
        $config = D('Static')->access('league_pvp', $_POST['area']);
        if(empty($config)){
            C('G_ERROR', 'league_arena_area_not_exist');
            return false;
        }

        //检查公会是否达到最低等级
        $needLevel = D('Static')->access('params', 'LEAGUE_AREA_LEVEL');
        if($needLevel > 1){
            $centerLevel = D('GLeague')->getAttr($this->mLeagueId, 'center_level');
            if($centerLevel < $needLevel){
                C('G_ERROR', 'league_center_level_low');
                return false;
            }
        }

        //检查公会活跃度是否足够
        $row = D('GLeagueArena')->getRow($this->mLeagueId);
        $exchange = $this->exchangeMoney('401', $row['count'] + 1);
        if (false === $moneyNow = $this->verify($exchange['needValue'], $this->mMoneyType[$exchange['needType']], $this->mLeagueId)) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除公会活跃度
        if (!$this->recover($this->mMoneyType[$exchange['needType']], $exchange['needValue'], $this->mLeagueId, $moneyNow)) {
            goto end;
        }

        //记录报名
        if (false === D('GLeagueArena')->record($this->mLeagueId, $_POST['area'], $this->mTid)) {
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