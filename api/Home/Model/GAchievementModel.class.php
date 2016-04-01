<?php
namespace Home\Model;

use Think\Model;

class GAchievementModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //领取成就奖励
    public function complete($tid, $achieve)
    {
        $data['tid'] = $tid;
        $data['achieve'] = $achieve;
        return $this->CreateData($data);
    }

    //查看成就奖励是否已经领取
    public function isCompleted($tid, $achieve)
    {
        $where['tid'] = $tid;
        $where['achieve'] = $achieve;
        $count = $this->where($where)->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //获取数据
    public function getList($tid)
    {
        $where['tid'] = $tid;
        $list = $this->where($where)->getField('achieve', true);
        if (empty($list)) {
            return array();
        }
        return $list;
    }

}