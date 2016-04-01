<?php
namespace Home\Model;

use Think\Model;

class LLeagueBattleModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );
}