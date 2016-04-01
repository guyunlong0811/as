<?php
namespace Home\Model;

use Think\Model;

class GShopModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //商店是否开启
    public function isOpen($type, $tid = 0)
    {
        $where['type'] = $type;
        $where['tid'] = $tid;
        $count = $this->where($where)->count();
        if ($count) {
            return true;
        } else {
            return false;
        }
    }

    //开启商店
    public function open($type, $dtime, $tid = 0)
    {
        $add['type'] = $type;
        $add['tid'] = $tid;
        $add['dtime'] = $dtime;
        return $this->CreateData($add);
    }

}