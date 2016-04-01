<?php
namespace Home\Model;

use Think\Model;

class LLoginModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //登陆日志
    public function cLog($tid)
    {
        $data['tid'] = $tid;
        return $this->CreateData($data);
    }

}