<?php
namespace Home\Model;

use Think\Model;

class GNoviceLoginModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //获取已经领取的数据
    public function getAll($tid)
    {
        $where['tid'] = $tid;
        $select = $this->field('day')->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $arr[] = $value['day'];
        }
        return $arr;
    }

    public function isReceived($tid, $day)
    {
        $where['tid'] = $tid;
        $where['day'] = $day;
        $count = $this->where($where)->count();
        if ($count >= 1) {
            return true;
        } else {
            return false;
        }
    }

}