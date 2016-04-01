<?php
namespace Home\Model;

use Think\Model;

class GDeviceModel extends BaseModel
{

    protected $_auto = array(
        array('utime', 'time', 3, 'function'), //更新时间
    );

    //创建数据
    public function cData($tid)
    {
        $data['tid'] = $tid;
        return $this->CreateData($data);
    }

    //创建数据
    public function uData($data)
    {

        $where['tid'] = $data['tid'];
        $count = $this->where($where)->count();
        if ($count == 0) {
            if ($this->cData($data['tid']))
                return $this->uData($data);
        } else {
            return $this->UpdateData($data);
        }

    }

}