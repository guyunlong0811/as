<?php
namespace Home\Model;

use Think\Model;

class LEquipUpgradeModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //装备升阶日志
    public function cLog($tid, $group, $target)
    {
        $data['tid'] = $tid;
        $data['group'] = $group;
        $data['index'] = $target;
        return $this->CreateData($data);
    }

    //查询玩家今天升阶装备次数
    public function getTodayCount($tid)
    {
        $today = get_daily_utime();
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', $today);
        return $this->where($where)->count();
    }

}