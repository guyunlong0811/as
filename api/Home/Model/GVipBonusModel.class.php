<?php
namespace Home\Model;

use Think\Model;

class GVipBonusModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //获取领取列表
    public function getList($tid)
    {
        $where['tid'] = $tid;
        $select = $this->where($where)->select();
        if (empty($select)) {
            return array();
        }

        $arr = array();
        foreach ($select as $value) {
            $arr[] = $value['vip'];
        }

        return $arr;
    }

    //是否已经领取过
    public function isReceived($tid, $vip)
    {
        $where['tid'] = $tid;
        $where['vip'] = $vip;
        $count = $this->where($where)->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }

    }

    //是否已经领取过
    public function receive($tid, $vip)
    {
        $add['tid'] = $tid;
        $add['vip'] = $vip;
        return $this->CreateData($add);
    }

}