<?php
namespace Home\Model;

use Think\Model;

class LMemberModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    public function cLog($tid, $type)
    {
        $where['tid'] = $tid;
        $where['type'] = array('like', $type . '%');
        $type = D('GMember')->where($where)->getField('type');
        $log['tid'] = $tid;
        $log['type'] = $type;
        return $this->CreateData($log);
    }

    //获取当日领取情况
    public function getCount($tid, $type)
    {
        $where['tid'] = $tid;
        $where['type'] = array('like', $type . '%');
        $where['ctime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

}