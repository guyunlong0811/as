<?php
namespace Home\Model;

use Think\Model;

class LInstanceModel extends BaseModel
{

    protected $_auto = array(
        array('endtime', 'time', 1, 'function'), //新增的时候把endtime字段设置为当前时间
    );

    //获取玩家当天完成某副本的次数
    public function getTodayList($tid)
    {
        $field = "`instance`,count(`id`) as `count`";
        $where['tid'] = $tid;
        $where['result'] = array('egt', 1);
        $where['starttime'] = array('egt', get_daily_utime());
        $select = $this->field($field)->where($where)->group('instance')->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[$value['instance']] = $value['count'];
        }
        return $list;
    }

    //获取玩家当天完成某副本的次数
    public function getTodayPartList($tid, $list)
    {
        $field = "`instance`,count(`id`) as `count`";
        $where['tid'] = $tid;
        $where['instance'] = array('in', $list);
        $where['result'] = array('egt', 1);
        $where['starttime'] = array('egt', get_daily_utime());
        $list = $this->field($field)->where($where)->group('instance')->select();
        if (empty($select)) {
            return array();
        }
        return $list;
    }

    //获取玩家当天完成某副本的次数
    public function getTodayCount($tid, $instance)
    {
        $where['tid'] = $tid;
        $where['instance'] = $instance;
        $where['result'] = array('egt', 1);
        $where['starttime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

    //获取玩家当天完成某副本组的次数
    public function getTodayGroupCount($tid, $group)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $where['result'] = array('egt', 1);
        $where['starttime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

    //获取玩家当天完成某难度的次数
    public function getTodayDifficultyCount($tid, $difficulty)
    {
        $where['tid'] = $tid;
        if ($difficulty == '0') {
            $where['difficulty'] = array('gt', $difficulty);
        } else {
            $where['difficulty'] = $difficulty;
        }
        $where['result'] = array('egt', 1);
        $where['starttime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

    //获取今日通天塔完成次数
    public function getTodayBabelCount($tid)
    {
        $where['tid'] = $tid;
        $where['module'] = 'Babel';
        $where['starttime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

}