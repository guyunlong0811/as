<?php
namespace Home\Model;

use Think\Model;

class TDailyInstanceModel extends BaseModel
{

    protected $_auto = array(
        array('count', 1),
        array('ctime', 'time', 1, 'function'),
        array('utime', 'time', 3, 'function'),
    );

    //插入数据
    public function record($tid, $instance_id)
    {
        $count = $this->getCount($tid, $instance_id);
        if ($count == 0) {
            $data['tid'] = $tid;
            $data['instance_id'] = $instance_id;
            if (!$this->CreateData($data)) {
                return false;
            }
            return true;
        } else {
            $where['tid'] = $tid;
            $where['instance_id'] = $instance_id;
            if (!$this->IncreaseData($where, 'count')) {
                return false;
            }
            return true;
        }
    }

    //获取次数
    public function getCount($tid, $instance_id)
    {
        $where['tid'] = $tid;
        $where['instance_id'] = $instance_id;
        $count = $this->where($where)->getField('count');
        $count = empty($count) ? 0 : $count;
        return $count;
    }

    //获取次数
    public function getList($tid)
    {
        $field = array('instance_id', 'count',);
        $where['tid'] = $tid;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $list[$value['instance_id']] = $value['count'];
        }
        return $list;
    }

}