<?php
namespace Home\Model;

use Think\Model;

class LStarModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //装备强化日志
    public function cLog($tid, $position, $level, $after)
    {
        $data['tid'] = $tid;
        $data['position'] = $position;
        $data['level'] = $level;
        $data['after'] = $after;
        return $this->CreateData($data);
    }
}