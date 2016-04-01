<?php
namespace Home\Model;

use Think\Model;

class GFateModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //获取已经领取的数据
    public function getCurrent($tid)
    {
        $where['tid'] = $tid;
        $index = $this->field('day')->where($where)->max('fate');
        $index = $index ? $index + 1 : 1;
        return $index;
    }

    //领取奖励
    public function round($tid, $fate, $bonus)
    {
        $add['tid'] = $tid;
        $add['fate'] = $fate;
        $add['bonus'] = $bonus;
        if (false === $this->CreateData($add)) {
            return false;
        }
        return true;
    }

}