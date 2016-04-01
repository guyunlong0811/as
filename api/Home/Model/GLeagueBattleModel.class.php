<?php
namespace Home\Model;

use Think\Model;

class GLeagueBattleModel extends BaseModel
{
    protected $_auto = array(
        array('partner', '[]'),
        array('airship', 0),
        array('battle', 1),
        array('monster', ''),
        array('hold_tid', 0),
        array('status', 2),
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //新增的时候把utime字段设置为当前时间
    );

    //获取单条数据
    public function getRow($league_id, $instance)
    {
        $where['league_id'] = $league_id;
        $where['instance'] = $instance;
        return $this->getRowCondition($where);
    }

    //获取某公会战况
    public function getAll($league_id)
    {
        $field = array('instance', 'utime', 'status',);
        $where['league_id'] = $league_id;
        $select = $this->field($field)->where($where)->select();
        if (empty($select))
            return array();
        foreach ($select as $value) {
            $list[$value['instance']] = $value;
        }
        return $list;
    }

    //获取玩家占领据点数
    public function getHoldCount($tid)
    {
        $where['hold_tid'] = $tid;
        return $this->where($where)->count();
    }

    //删除所有未结束的挑战并记录
    public function destroy($tid, $instance)
    {
        $where = "(`last_tid`='{$tid}' || `instance`='{$instance}') && (`status`='2' || `status`='3')";
        $select = $this->where($where)->select();
        if (empty($select)) {
            return true;
        }

        //记录日志
        $now = time();
        foreach ($select as $value) {
            //记录日志
            $log['tid'] = $value['last_tid'];
            $log['league_id'] = $value['league_id'];
            $log['instance'] = $value['instance'];
            $log['partner'] = field2string($value['partner']);
            $log['airship'] = $value['airship'];
            $log['result'] = -2;
            $log['ctime'] = $now;
            $all[] = $log;
        }
        D('LLeagueBattle')->CreateAllData($all);

        //删除所有站在对战的记录
        $update['status'] = 0;
        if (false === $this->UpdateData($update, $where)) {
            return false;
        }
        return count($select);
    }

}