<?php
namespace Home\Model;

use Think\Model;

class LShareModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //创建日志
    public function cLog($tid)
    {
        $data['tid'] = $tid;
        return $this->CreateData($data);
    }

    //查询当日分享次数
    public function getTodayCount($tid)
    {
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

}