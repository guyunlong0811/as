<?php
namespace Home\Api;

use Think\Controller;

class RankApi extends BaseApi
{

    static private $type = array(
        //战队
        101 => 'teamLevel',
        102 => 'vipLevel',
        103 => 'star',
        104 => 'combo',
        105 => 'todayCombo',
        106 => 'arenaWinContinuous',
        107 => 'force',
        108 => 'forceTop',
        109 => 'arena',
        110 => 'achievement',

        //公会
        201 => 'league',
        202 => 'leagueFight',

    );

    //返回排行榜
    public function getList()
    {
        $function = self::$type[$_POST['type']];
        $return['type'] = $_POST['type'];
        $rs = $this->$function();
        $return = $return + $rs;
        return $return;
    }

    //获取战队等级排行榜
    private function teamLevel()
    {

        //获取当前自己的排名
        $return = D('GTeam')->getCurrentLevelRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'team');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GTeam')->getRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'team', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取VIP等级排行榜
    private function vipLevel()
    {

        //获取当前自己的排名
        $return = D('GVip')->getCurrentVipLevelRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'vip');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GVip')->getRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'vip', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取副本星数排行榜
    private function star()
    {

        //获取当前自己的排名
        $return = D('GCount')->getCurrentStarRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'star');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GCount')->getStarRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'star', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取最高COMBO数排行榜
    private function combo()
    {

        //获取当前自己的排名
        $return = D('GCount')->getCurrentComboRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'combo');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GCount')->getComboRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'combo', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取当日最高COMBO数排行榜
    private function todayCombo()
    {

        //获取当前自己的排名
        $return = D('TDailyCount')->getCurrentTodayComboRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'combo_today');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('TDailyCount')->getComboRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'combo_today', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        if (empty($list)) {
            $list = array();
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取竞技场连胜榜
    private function arenaWinContinuous()
    {

        //获取当前自己的排名
        $return = D('GCount')->getCurrentArenaWinContinuousRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'arena_win_continuous');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GCount')->getArenaWinContinuousRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'arena_win_continuous', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取战力排行榜
    private function force()
    {

        //获取当前自己的排名
        $return = D('GCount')->getCurrentForceRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'force');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GCount')->getForceRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'force', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取最强小队战力排行榜
    private function forceTop()
    {

        //获取当前自己的排名
        $return = D('GCount')->getCurrentForceTopRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'force_top');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GCount')->getForceTopRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'force_top', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }

    //获取竞技场前50名
    private function arena()
    {

        //获取当前自己的排名
        $rank = D('GArena')->getAttr($this->mTid, 'rank');
        $rank = $rank ? $rank : 0;
        $return['current'] = $rank;
        $return['rank'] = $rank;

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'arena');

        if (empty($json)) {
            $list = D('GArena')->getRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'arena', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;
    }

    //
    private function achievement()
    {

        //获取当前自己的排名
        $return = D('GCount')->getCurrentAchievementRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'achievement');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GCount')->getAchievementRankList();
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'achievement', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;

    }


    //获取公会排行榜
    private function league()
    {

        //获取当前自己的排名
        $return['rank'] = D('TDailyLeague')->getRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'league');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('TDailyLeague')->getList(0, C('RANK_MAX'));
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'league', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;
    }

    //获取公会积分(公会战)排行榜
    private function leagueFight()
    {

        //获取当前自己的排名
        $return = D('GLeagueRank')->getRank($this->mTid);

        //获取排行榜json
        $json = D('Predis')->cli('game')->hget('rank', 'league_fight');

        //如果redis没有则重新生成
        if (empty($json)) {
            $list = D('GLeagueRank')->getList(0, C('RANK_MAX'));
            $jsonRank = json_encode($list);
            D('Predis')->cli('game')->hset('rank', 'league_fight', $jsonRank);
        } else {
            $list = json_decode($json, true);
        }

        //返回数据
        $return['list'] = (array)$list;
        return $return;
    }

    //查询玩家竞技场防御阵容基本信息
    public function getDefenseInfo()
    {

        //获取玩家竞技场防御阵容
        $row = D('GArena')->getRow($_POST['target_tid'], array('partner',));
        if (empty($row)) {
            return array();
        }

        //解析json
        $defense = json_decode($row['partner'], true);

        //获取信息
        $field = array('group', 'index', 'level', 'favour', 'force',);
        $where = "`tid`='{$_POST['target_tid']}' && (";
        foreach ($defense as $value) {
            $where .= "`group`='{$value}' || ";
        }
        $where = substr($where, 0, -4) . ')';
        $list = M('GPartner')->field($field)->where($where)->select();

        //返回
        return $list;

    }

}