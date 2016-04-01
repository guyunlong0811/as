<?php
namespace Home\Model;

use Think\Model;

class GLeagueModel extends BaseModel
{

    protected $_validate = array(//自动验证
        array('name', '', 'league_name_existed', 0, 'unique', 3), //在新增的时候验证name字段是否唯一
        array('president_tid', '', 'league_already_attended', 0, 'unique', 3), //在新增的时候验证name字段是否唯一
    );

    protected $_auto = array(
        array('fund', 0),
        array('notice', ''),
        array('center_level', 1),
        array('shop_level', 1),
        array('food_level', 1),
        array('attribute_level', 1),
        array('boss_level', 1),
        array('record', ''),
        array('recommend', 0),
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //获取信息
    public function getInfo($leagueId)
    {
        $info = $this->field('`g_league`.`id`,`g_league`.`name`,`g_team`.`tid`,`g_team`.`nickname`,`g_league`.`president_tid`,`g_league`.`fund`,`g_league`.`notice`,`g_league`.`center_level`,`g_league`.`shop_level`,`g_league`.`food_level`,`g_league`.`attribute_level`,`g_league`.`boss_level`,`g_league`.`activity`')->join('`g_team` ON `g_team`.`tid` = `g_league`.`president_tid`')->where("`g_league`.`id` = '{$leagueId}'")->find();
        $info['activity'] = $this->getActivity($leagueId, $info['activity']);
        return $info;
    }

    //获取当前活跃值
    public function getActivity($leagueId, $activityNow = null)
    {
        $activity = D('Predis')->cli('game')->hget('lg:' . $leagueId, 'activity');
        if (empty($activity) && $activity !== '0') {
            if (!$activityNow) {
                $activityNow = $this->getAttr($leagueId, 'activity');
            }
            D('Predis')->cli('game')->hset('lg:' . $leagueId, 'activity', $activityNow);
        } else {
            $activityNow = $activity;
        }
        return $activityNow;
    }

    //搜索公会
    public function search($leagueId)
    {
        $field = "`g_league`.`id` as `league_id`,`g_league`.`name` as `league_name`,`g_team`.`tid` as `president_tid`,`g_team`.`nickname` as `president_nickname`,`g_league`.`center_level` as `center_level`,count(`g_league_team`.`tid`) as `count`";
        $where = "`g_league`.`id`='{$leagueId}'";
        return $this->field($field)->join("`g_team` on `g_team`.`tid`=`g_league`.`president_tid`")->join("`g_league_team` on `g_league_team`.`league_id`=`g_league`.`id`")->where($where)->group('`g_league_team`.`league_id`')->select();
    }

    //获取属性值
    public function getRow($leagueId, $field = null)
    {
        $where['id'] = $leagueId;
        return $this->getRowCondition($where, $field);
    }

    //获取属性值
    public function getAttr($leagueId, $attr)
    {
        $where['id'] = $leagueId;
        return $this->where($where)->getField($attr);
    }

    //增加属性
    public function incAttr($leagueId, $attr, $value = 1, $before = null)
    {
        if ($value == 0) {
            return true;
        }
        if ($before === null) {
            $before = $this->getAttr($leagueId, $attr);
        }
        $where['id'] = $leagueId;
        if (!$this->IncreaseData($where, $attr, $value)) {
            return false;
        }
        D('LLeague')->cLog($leagueId, $attr, $value, $before);//日志
        return true;
    }

    //增加公会活跃度
    public function incAttrActivity($tid, $value = 1)
    {
        //获取公会ID
        $leagueId = D('GLeagueTeam')->getAttr($tid, 'league_id');
        if (empty($leagueId)) {
            return true;
        }

        //获取当前信息
        $row = $this->getRow($leagueId, array('center_level', 'activity'));

        //获取公会活跃度上限
        $config = D('Static')->access('league', $row['center_level']);

        //计算到达总上限的值
        $lackMax = $config['activevalue_max'] - $row['activity'];

        //总上限限制
        if($lackMax <= 0){
            return true;
        }

        //查询今天公会获得活跃度
        $today = D('LLeague')->getTodayCount($leagueId, 'activity');

        //计算到达今日上限的值
        $lackToday = $config['activevalue_daily'] - $today;

        //今日上限
        if ($lackToday <= 0) {
            return true;
        }

        //计算最终缺少的值
        $lack = min($lackMax, $lackToday);

        //最终增加值
        $value = min($value, $lack);

        //增加属性
//        return $this->incAttr($leagueId, 'activity', $value, $row['activity']);

        $after = D('Predis')->cli('game')->hincrby('lg:' . $leagueId, 'activity', $value);
        $log['league_id'] = $leagueId;
        $log['attr'] = 'activity';
        $log['value'] = $value;
        $log['before'] = $after - $value;
        $log['after'] = $after;
        $log['behave'] = C('G_BEHAVE') > 0 ? C('G_BEHAVE') : get_config('behave', array(C('G_BEHAVE'), 'code',));//获取改变原因
        D('LLeague')->CreateData($log);//日志

        return true;

    }

    //减少属性
    public function decAttr($leagueId, $attr, $value = 1, $before = null)
    {
        if ($value == 0) {
            return true;
        }
        if ($before === null) {
            $before = $this->getAttr($leagueId, $attr);
        }
        $where['id'] = $leagueId;
        if (!$this->DecreaseData($where, $attr, $value)) {
            return false;
        }
        D('LLeague')->cLog($leagueId, $attr, -$value, $before);//日志
        return true;
    }

    //改变属性
    public function updateAttr($leagueId, $attr, $value, $before = null)
    {
        if ($before === null) {
            $before = $this->getAttr($leagueId, $attr);
        }
        $where['id'] = $leagueId;
        $data[$attr] = $value;
        if (false === $this->UpdateData($data, $where)) {
            return false;
        }
        D('LLeague')->cLog($leagueId, $attr, $value, $before);//日志
        return true;
    }

    //获取角色基本属性
    public function getRowMember($tid)
    {
        return $this->field('`g_league`.`id`,`g_league`.`name`,`g_league`.`center_level`,`g_league`.`point`')->join('`g_league_team` ON `g_league_team`.`league_id` = `g_league`.`id`')->where("`g_league_team`.`tid` = '{$tid}'")->find();
    }

    //获取玩家公会情况
    public function getLeagueShopLevel($tid)
    {
        return $this->field('`g_league`.`shop_level`')->join('`g_league_team` ON `g_league_team`.`league_id` = `g_league`.`id`')->where("`g_league_team`.`tid` = '{$tid}'")->find();
    }

    //获取所有公会攻占据点的情况
    public function battleComplete()
    {
        $field = "`g_league`.`id`,`g_league`.`president_tid`,count(`g_league_battle`.`league_id`) as `count`";
        $where = "`g_league_battle`.`status`=1";
        return $this->field($field)->join("`g_league_battle` on `g_league`.`id`=`g_league_battle`.`league_id`")->where($where)->group('`g_league_battle`.`league_id`')->select();
    }

    //获取推荐列表
    public function getRecommendList($start, $row)
    {
        $now = time();
        $field = "`g_league`.`id` as `league_id`,`g_league`.`name` as `league_name`,`g_team`.`tid` as `president_tid`,`g_team`.`nickname` as `president_nickname`,`g_league`.`center_level` as `center_level`,count(`g_league_team`.`tid`) as `count`";
        $where = "`g_league`.`recommend`>'{$now}'";
        return $this->field($field)->join("`g_team` on `g_team`.`tid`=`g_league`.`president_tid`")->join("`g_league_team` on `g_league_team`.`league_id`=`g_league`.`id`")->where($where)->order('`g_league`.`recommend` DESC')->group('`g_league_team`.`league_id`')->limit($start, $row)->select();
    }

    //获取推荐公会个数
    public function getRecommendCount()
    {
        $now = time();
        $where = "`g_league`.`recommend`>'{$now}'";
        return $this->where($where)->count();
    }

    //获取推荐列表
    public function getList()
    {
        $field = "`gl`.`id` as `league_id`,`gl`.`name` as `league_name`,`gl`.`president_tid`,`gt`.`nickname` as `president_nickname`,`gl`.`center_level`,count(`glt`.`tid`) as `count`";
        $table = array('g_league' => 'gl', 'g_team' => 'gt', 'g_league_team' => 'glt');
        $where = "`gl`.`id` = `glt`.`league_id` && `gl`.`president_tid` = `gt`.`tid`";
        return $this->field($field)->table($table)->where($where)->group('`glt`.`league_id`')->select();
    }

    //获取公会等级
    public function getCenterLevel($tid)
    {
        $table = array('g_league' => 'gl', 'g_league_team' => 'glt');
        $where = "`gl`.`id` = `glt`.`league_id` && `glt`.`tid` = '{$tid}'";
        $level = $this->table($table)->where($where)->getField("`gl`.`attribute_level`");
        $level = $level ? $level : 0;
        return $level;
    }

    //获取积分排名
    public function rank()
    {
        $field = array('`gl`.`id` as `league_id`', '`gl`.`name` as `league_name`', '`gl`.`president_tid`', '`gt`.`nickname` as `president_nickname`', '`gl`.`center_level`', 'count(`glt`.`league_id`) as `count`', '`gl`.`point`');
        $table = array('g_league' => 'gl', 'g_league_team' => 'glt', 'g_team' => 'gt');
        $where = "`gl`.`id` = `glt`.`league_id` && `gl`.`president_tid` = `gt`.`tid`";
        return $this->field($field)->table($table)->where($where)->group('`glt`.`league_id`')->select();
    }

}