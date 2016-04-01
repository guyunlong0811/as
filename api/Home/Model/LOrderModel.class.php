<?php
namespace Home\Model;

use Think\Model;

class LOrderModel extends BaseModel
{

    protected $_auto = array(
        array('endtime', 'time', 1, 'function'),
    );

    //查询今天充值次数
    public function getTodayCount($tid)
    {
        $where['tid'] = $tid;
        $where['status'] = 1;
        $where['endtime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

}