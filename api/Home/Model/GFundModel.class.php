<?php
namespace Home\Model;

use Think\Model;

class GFundModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //领取成就奖励
    public function receive($tid, $fund)
    {
        $data['tid'] = $tid;
        $data['fund'] = $fund;
        return $this->CreateData($data);
    }

    //查看成就奖励是否已经领取
    public function isCompleted($tid, $fund)
    {
        $where['tid'] = $tid;
        $where['fund'] = $fund;
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
        $list = $this->where($where)->getField('fund', true);
        if (empty($list)) {
            return array();
        }
        return $list;
    }

}