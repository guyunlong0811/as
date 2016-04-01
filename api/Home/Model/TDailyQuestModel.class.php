<?php
namespace Home\Model;

use Think\Model;

class TDailyQuestModel extends BaseModel
{

    protected $_auto = array(
        array('count', 0), //完成次数
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
    );

    //查询已完成&正在进行的任务
    public function getAll($tid)
    {
        $field = array('quest', 'count',);
        $where['tid'] = $tid;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $list[$value['quest']] = $value['count'];
        }
        return $list;
    }

    //查询已经完成的任务
    public function getFinish($tid)
    {
        $field = array('quest',);
        $where['tid'] = $tid;
        $where['count'] = array('egt', 1);
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $list[] = $value['quest'];
        }
        return $list;
    }

}