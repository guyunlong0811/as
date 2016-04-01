<?php
namespace Home\Model;

use Think\Model;

class GLevelBonusModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //领取奖励
    public function receive($tid, $bonus)
    {
        $data['tid'] = $tid;
        $data['bonus'] = $bonus;
        return $this->CreateData($data);
    }

    //查看奖励是否已经领取
    public function isReceived($tid, $bonus)
    {
        $where['tid'] = $tid;
        $where['bonus'] = $bonus;
        $count = $this->where($where)->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //获取已领取列表
    public function getReceived($tid)
    {
        $where['tid'] = $tid;
        $list = $this->where($where)->getField('bonus', true);
        if (empty($list)) {
            return array();
        } else {
            return $list;
        }
    }

}