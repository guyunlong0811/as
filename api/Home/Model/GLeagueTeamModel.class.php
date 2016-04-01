<?php
namespace Home\Model;

use Think\Model;

class GLeagueTeamModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
        array('contribution', '0'),
        array('boss_buff', '0'),
        array('quest_list', '[]'),
    );

    public function _initialize()
    {
        $quest_count = D('Static')->access('params', 'LEAGUE_QUEST_COUNT');
        $_auto[] = array('quest_count', $quest_count);//新增的时候把quest_count字段设置为$quest_count
    }

    //获取公会当前人数
    public function getMemberCount($league)
    {
        $where['league_id'] = $league;
        return $this->where($where)->count();
    }

    //获取角色基本属性
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //获取公会进度
    public function getSchedule($leagueId)
    {
        $field = "`g_league_team`.`tid` as `tid`,count(`g_league_battle`.`instance`) as `hold`";
        $where = "`g_league_team`.`league_id`='{$leagueId}'";
        $select = $this->field($field)->join("left join `g_league_battle` ON `g_league_team`.`tid` = `g_league_battle`.`hold_tid`")->where($where)->group('`g_league_team`.`tid`')->select();
        if (empty($select)) {
            return array();
        }
        return $select;
    }

    //获取角色基本属性
    public function getAttr($tid, $attr)
    {
        if ($attr == 'league_id') {
            $leagueId = D('Predis')->cli('game')->hget('s:' . $tid, 'league_id');
            if ($leagueId > 0) {
                return $leagueId;
            }
        }
        $where['tid'] = $tid;
        return $this->where($where)->getField($attr);
    }

    //增加属性
    public function incAttr($tid, $attr, $value = 1, $before = null, $leagueId = null)
    {
        if ($value == 0) {
            return true;
        }
        if (is_null($leagueId)) {
            $leagueId = $this->getAttr($tid, 'league_id');
            if (is_null($leagueId)) {
                return true;
            }
        }
        if ($attr == 'boss_buff') {
            //查询当前值
            $now = $this->getAttr($tid, $attr);
            //最大值对比
            $max = D('Static')->access('params', 'LEAGUE_BOSS_SKILL_MAX');
            if ($now >= $max) {
                return true;
            }
            //超最大值重新计算添加值
            if ($now + $value >= $max) {
                $value = $max - $now;
            }
        }
        $where['tid'] = $tid;
        if (!$this->IncreaseData($where, $attr, $value)) {
            return false;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }
        D('LLeagueTeam')->cLog($tid, $leagueId, $attr, $value, $before);//日志
        return true;
    }

    //减少属性
    public function decAttr($tid, $attr, $value = 1, $before = null, $leagueId = null)
    {
        if ($value == 0) {
            return true;
        }
        //buff特殊处理
        if ($attr == 'boss_buff') {
            //查询当前值
            $before = $this->getAttr($tid, $attr);
            if ($before <= 0) {
                return true;
            }
        }
        $where['tid'] = $tid;
        if (!$this->DecreaseData($where, $attr, $value)) {
            return false;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }
        if ($leagueId === null) {
            $leagueId = $this->getAttr($tid, 'league_id');
        }
        D('LLeagueTeam')->cLog($tid, $leagueId, $attr, -$value, $before);//日志
        return true;
    }

    //改变属性
    public function updateAttr($tid, $attr, $value, $before = null, $leagueId = null)
    {
        $where['tid'] = $tid;
        $data[$attr] = $value;
        if (false === $this->UpdateData($data, $where)) {
            return false;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }
        if ($leagueId === null) {
            $leagueId = $this->getAttr($tid, 'league_id');
        }
        D('LLeagueTeam')->cLog($tid, $leagueId, $attr, $value, $before);//日志
        return true;
    }

    //获取公会成员
    public function getALL($leagueId)
    {
        $field = "`g_team`.`tid`,`g_team`.`nickname`";
        $where = "`g_league_team`.`league_id`='{$leagueId}'";
        return $this->field($field)->join("`g_team` on `g_team`.`tid`=`g_league_team`.`tid`")->where($where)->select();
    }

    //获取获胜公会成员
    public function getALLTid($leagueId)
    {
        $field = array('tid',);
        $where['league_id'] = $leagueId;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $data[] = $value['tid'];
        }
        return $data;
    }

}