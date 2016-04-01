<?php
namespace Home\Model;

use Think\Model;

class LActivityCompleteModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //领取成就奖励
    public function cLog($tid, $activity)
    {
        $data['tid'] = $tid;
        $data['activity'] = $activity;
        return $this->CreateData($data);
    }

    //获取数据
    public function getList($tid)
    {
        $where['tid'] = $tid;
        $select = $this->field("`activity`,count(`id`) as `count`")->where($where)->group('activity')->select();
        if (empty($select)) {
            return array();
        } else {
            $list = array();
            foreach ($select as $value) {
                $list[$value['activity']] = $value['count'];
            }
            return $list;
        }
    }

    //获取数据
    public function getTodayList($tid)
    {
        $where['tid'] = $tid;
        $start = get_daily_utime();
        $end = $start + 86399;
        $where['ctime'] = array('between', array($start, $end));
        $select = $this->field("`activity`,count(`id`) as `count`")->where($where)->group('activity')->select();
        if (empty($select)) {
            return array();
        } else {
            $list = array();
            foreach ($select as $value) {
                $list[$value['activity']] = $value['count'];
            }
            return $list;
        }
    }

}