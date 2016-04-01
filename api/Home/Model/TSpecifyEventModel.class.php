<?php
namespace Home\Model;

use Think\Model;

class TSpecifyEventModel extends BaseModel
{

    protected $_auto = array(
        array('count', 1),
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
    );

    //插入数据
    public function record($tid, $event_id, $type, $group)
    {
        $count = $this->getCount($tid, $event_id, $type);
        if ($count == 0) {
            $data['tid'] = $tid;
            $data['event_id'] = $event_id;
            $data['type'] = $type;
            $data['group'] = $group;
            if (!$this->CreateData($data)) {
                return false;
            }
            return true;
        } else {
            $where['tid'] = $tid;
            $where['type'] = $type;
            if (!$this->IncreaseData($where, 'count')) {
                return false;
            }
            return true;
        }
    }

    //获取次数
    public function getCount($tid, $event_id, $type)
    {
        $where['tid'] = $tid;
        $where['event_id'] = $event_id;
        $where['type'] = $type;
        $count = $this->where($where)->getField('count');
        $count = empty($count) ? 0 : $count;
        return $count;
    }

    //获取次数
    public function getGroupCount($tid, $group, $type)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $where['type'] = $type;
        $count = $this->where($where)->sum('count');
        $count = empty($count) ? 0 : $count;
        return $count;
    }

}