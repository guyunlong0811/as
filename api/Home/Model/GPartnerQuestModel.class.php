<?php
namespace Home\Model;

use Think\Model;

class GPartnerQuestModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
        array('status', 0), //任务状态
    );

    //查询已完成&正在进行的任务
    public function getAll($tid)
    {
        $field = array('quest', 'status',);
        $where['tid'] = $tid;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $list[$value['quest']] = $value['status'];
        }
        return $list;
    }

}