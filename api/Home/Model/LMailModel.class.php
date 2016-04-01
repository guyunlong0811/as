<?php
namespace Home\Model;

use Think\Model;

class LMailModel extends BaseModel
{

    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
    );

    //邮件日志
    public function cLog($mail, $status)
    {
        unset($mail['id']);
        unset($mail['status']);
        $data = $mail;
        $data['status'] = $status;
        return $this->CreateData($data);
    }

}