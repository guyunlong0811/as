<?php
namespace Home\Model;

use Think\Model;

class LArenaBattleModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //获取最近战斗记录
    public function getList($tid, $limit = 10)
    {
        $select = $this->field('id', true)->where("`tid1`='{$tid}' || `tid2`='{$tid}'")->order('`ctime` DESC')->limit($limit)->select();
        if (empty($select)) {
            return array();
        } else {
            return $select;
        }
    }

}