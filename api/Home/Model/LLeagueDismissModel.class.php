<?php
namespace Home\Model;

use Think\Model;

class LLeagueDismissModel extends BaseModel
{
    protected $_auto = array(
        array('dtime', 'time', 1, 'function'), //新增的时候把dtime字段设置为当前时间
    );
}