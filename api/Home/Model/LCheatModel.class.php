<?php
namespace Home\Model;

use Think\Model;

class LCheatModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    public function cLog($tid, $type, $value, $normal = '')
    {
        $data['tid'] = $tid;
        $data['type'] = $type;
        $data['value'] = $value;
        $data['normal'] = $normal;
        return $this->CreateData($data);
    }
}