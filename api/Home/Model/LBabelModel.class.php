<?php
namespace Home\Model;

use Think\Model;

class LBabelModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //查询玩家今天到达的最高层数
    public function getTodayMax($tid)
    {
        $today = get_daily_utime();
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', $today);
        return $this->where($where)->max('floor');
    }
}