<?php
namespace Home\Model;

use Think\Model;

class TDailyActivityBonusModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //查询是否已经领取
    public function isReceived($tid, $bonus)
    {
        $where['tid'] = $tid;
        $where['bonus'] = $bonus;
        $count = $this->where($where)->count();
        if ($count == 0) {
            return false;
        }
        return true;
    }

    //获取已领取奖励列表
    public function getReceived($tid)
    {
        $where['tid'] = $tid;
        $list = $this->where($where)->getField('bonus', true);
        $list = empty($list) ? array() : $list;
        return $list;
    }

    //获取当日连击排行榜
    public function receive($tid, $bonus)
    {
        $add['tid'] = $tid;
        $add['bonus'] = $bonus;
        return $this->CreateData($add);
    }

}