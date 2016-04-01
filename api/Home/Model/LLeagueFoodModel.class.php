<?php
namespace Home\Model;

use Think\Model;

class LLeagueFoodModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //竞技场日志
    public function cLog($tid, $league_id)
    {
        $data['tid'] = $tid;
        $data['league_id'] = $league_id;
        return $this->CreateData($data);
    }

    //获取今天使用食堂次数
    public function getCount($tid)
    {
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

}