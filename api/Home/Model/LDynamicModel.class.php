<?php
namespace Home\Model;

use Think\Model;

class LDynamicModel extends BaseModel
{

    protected $_auto = array(
        array('endtime', 'time', 1, 'function'), //新增的时候把endtime字段设置为当前时间
    );

    //查询玩家今天挑战次数
    public function getTodayLifeDeathCount($tid)
    {
        $today = get_daily_utime();
        $where['tid'] = $tid;
        $where['module'] = 'LifeDeathBattle';
        $where['endtime'] = array('egt', $today);
        return $this->where($where)->count();
    }

}