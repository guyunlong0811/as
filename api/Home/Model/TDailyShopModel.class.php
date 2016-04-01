<?php
namespace Home\Model;

use Think\Model;

class TDailyShopModel extends BaseModel
{

    protected $_auto = array(
        array('count', 1),
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
    );

    //插入数据
    public function record($tid, $type)
    {
        $count = $this->getCount($tid, $type);
        if ($count == 0) {
            $data['tid'] = $tid;
            $data['type'] = $type;
            if (!$this->CreateData($data)) {
                return false;
            }
            return true;
        } else {
            $where['tid'] = $tid;
            $where['type'] = $type;
            if (!$this->IncreaseData($where, 'count')) {
                return false;
            }
            return true;
        }
    }

    //获取次数
    public function getCount($tid, $type)
    {
        $where['tid'] = $tid;
        $where['type'] = $type;
        $count = $this->where($where)->getField('count');
        $count = empty($count) ? 0 : $count;
        return $count;
    }

}