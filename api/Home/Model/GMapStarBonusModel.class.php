<?php
namespace Home\Model;

use Think\Model;

class GMapStarBonusModel extends BaseModel
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

    //查看是否已经领取奖励
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

    //获取数据
    public function getList($tid)
    {
        $field = 'bonus';
        $where['tid'] = $tid;
        $data = $this->field($field)->where($where)->select();
        if (empty($data)) {
            return array();
        } else {
            foreach ($data as $value) {
                $arr[] = $value['bonus'];
            }
            return $arr;
        }
    }

}