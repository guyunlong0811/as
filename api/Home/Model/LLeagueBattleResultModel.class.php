<?php
namespace Home\Model;

use Think\Model;

class LLeagueBattleResultModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //新增的时候把ctime字段设置为当前时间
    );
}